<?php

namespace Oktolab\MediaBundle\Model;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

use Oktolab\MediaBundle\Entity\Stream;
use Oktolab\MediaBundle\OktolabMediaEvent;
use Oktolab\MediaBundle\Event\StartRecordingStreamEvent;
use Oktolab\MediaBundle\Event\EndRecordingStreamEvent;

/**
* handles import worker, jobs for worker and permission handling
*/
class StreamService
{
    private $dispatcher; // event dispatcher
    private $em;
    private $stream_class;
    private $streamserver_config;
    private $default_streamserver_config;

    public function __construct($stream_class, $dispatcher, $em, array $streamserver_config, $default_streamserver_config)
    {
        $this->dispatcher = $dispatcher;
        $this->em = $em;
        $this->stream_class = $stream_class;
        $this->streamserver_config = $streamserver_config;
        $this->default_streamserver_config = $default_streamserver_config;
    }

    /**
     * returns a new stream object preset with default streamserver config
     */
    public function createStream($serverconfig = null)
    {
        if ($serverconfig == null) {
            $serverconfig = $this->default_streamserver_config;
        }
        $stream = new $this->stream_class;
        $stream->setRtmpServer($this->streamserver_config[$serverconfig]['rtmp_url']);
        $stream->setRtmpApp($this->streamserver_config[$serverconfig]['app']);
        return $stream;
    }

    public function getStream($uniqID)
    {
        return $this->em->getRepository($this->stream_class)->findOneBy(['uniqID' => $uniqID]);
    }

    public function setStreamStatus($stream, $status)
    {
        $stream->setTechnicalStatus($status);
        $this->em->persist($stream);
        $this->em->flush();
        // TODO: dispatch STREAM_CHANGED_TECHNICAL_STATUS event
    }

    public function getServeradress($stream)
    {
        return sprintf(
            '%s/%s',
            $this->streamserver_config[$stream->getRtmpServer()]['rtmp_url'],
            $stream->getUniqID());
    }

    public function getPublicServeradress($stream)
    {
        return sprintf(
            $this->streamserver_config[$stream->getRtmpServer()]['player_url'], $stream->getUniqID()
        );
    }

    /**
     * start recording a stream.
     */
    public function startRecording($stream)
    {
        $parts = explode('/', $this->streamserver_config[$stream->getRtmpServer()]['rtmp_url']);
        $rtmp_app = end($parts);
        $success = $this->record(
            $this->streamserver_config[$stream->getRtmpServer()]['rtmp_control'],
            $rtmp_app,
            $stream->getUniqID()
        );
        if ($success) {
            $stream->setTechnicalStatus(Stream::STATE_RECORDING);
            $this->em->persist($stream);
            $this->em->flush();

            $event = new StartRecordingStreamEvent($stream);
            $this->dispatcher->dispatch(
                OktolabMediaEvent::STREAM_START_RECORDING,
                $event
            );
            return true;
        }
        return false;
    }

    /**
     * end recording a stream
     */
    public function endRecording($stream)
    {
        $parts = explode('/', $this->streamserver_config[$stream->getRtmpServer()]['rtmp_url']);
        $rtmp_app = end($parts);
        $success = $this->record(
            $this->streamserver_config[$stream->getRtmpServer()]['rtmp_control'],
            $rtmp_app,
            $stream->getUniqID(),
            false
        );
        if ($success) {
            $stream->setTechnicalStatus(Stream::STATE_ENDED);
            $this->em->persist($stream);
            $this->em->flush();

            // $event = new EndRecordingStreamEvent($stream);
            // $this->dispatcher->dispatch(
            //     OktolabMediaEvent::STREAM_END_RECORDING,
            //     $event
            // );
            return true;
        }
        return false;
    }

    /**
     * rtmp server sends this command if the stream ended.
     * could potentially be because something went wrong.
     */
    public function streamEnded($stream)
    {
        if ($stream->getTechnicalStatus() == Stream::STATE_RECORDING) {
            // TODO: add loogbook and log an unclean stream stop
        }
        // $event = new EndRecordingStreamEvent($stream);
        // $this->dispatcher->dispatch(
        //     OktolabMediaEvent::STREAM_ENDED,
        //     $event
        // );
    }

    /**
     * adds a push to another rtmp server. can be used to send a stream to facebook, youtube, etc.
     * @var stream the stream you want to push
     * @var rtmp_adress the adress you want to push to
     * @var cientid the streamkey of the destination
     * @var new_name the app of the rtmp server you want to push to
     * @var srv optional nginx server to use
     */
    public function addPublishToAdress($stream, $rtmp_adress, $clientid, $new_name, $type = 'publisher', $srv = null)
    {
        return $this->redirect(
            $stream->getRtmpApp(),
            $stream->getUniqID(),
            $rtmp_adress,
            $clientid,
            $new_name,
            $type,
            $srv
        );
    }

    public function dropPublishToAdress($stream, $rtmp_adress, $clientid, $type = 'publisher', $srv = null)
    {
        return $this->drop(
            $stream->getRtmpApp(),
            $stream->getUniqID(),
            $type,
            $rtmp_adress,
            $clientid,
            $srv
        );
    }

    /**
     * sends request to start/stop recording of a stream
     *  @var app required rtmp application name (see rtmp server config)
     *  @var name required rtmp stream name (see Entity Stream uniqID)
     *  @var start true if you want to start recording, false if you want to stop it
     *  @var srv optional server{} blocknumber in nginx rtmp config, uses first one by default
     *  @var rec optional recorder name to use in nginx rtmp config. defaults to root (unnamed) recorder
     *  @return true if command was accepted, false if something went wrong
     */
    public function record($control_url, $app, $name, $start = true, $srv = null, $rec = null)
    {
        $query = [];
        $query['app'] = $app;
        $query['name'] = $name;
        if ($srv) {
            $query['srv'] = $srv;
        }
        if ($rec) {
            $query['rec'] = $rec;
        }
        $url = null;        
        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $start ? $control_url .'/record/start' : $control_url .'/record/stop',
                [
                    'query' => $query
                ]
            );
        } catch (RequestException $e) {
            return false;
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * send a drop command to the rtmp server
     * @var app the app name
     * @var name the streamkey
     * @var type the type for your drop command publisher|subscriber|client
     * @var addr the optional client adress
     * @var clientId the optional nginx client id
     * @var srv optional server{} block number within rtmp{} block. default the first is used (nginx rtmp)
     */
    public function drop($control_url, $app, $name, $type = 'publisher', $addr = null, $clientid = null, $srv = null)
    {
        $query = [];
        $query['app'] = $app;
        $query['name'] = $name;

        if ($addr) {
            $query['addr'] = $addr;
        }
        if ($clientid) {
            $query['clientid'] = $clientid;
        }
        if ($srv) {
            $query['srv'] = $srv;
        }

        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $url .'/control/drop/'.$type,
                [
                    'query' => $query
                ]
            );
        } catch (RequestException $e) {
            return false;
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * sends a redirect command to the rtmp server
     * @var app the rtmp app name
     * @var name the streamkey
     * @var new_adress the optional client adress
     * @var clientId the optional nginx client id
     * @var new_name new stream name to redirect to (streamkey)
     * @var new_clientid optional nginx client id
     */
    public function redirect($control_url, $app, $name, $new_adress, $new_clientid, $new_name, $type = 'publisher', $srv = null)
    {
        $query = [];
        $query['app'] = $app;
        $query['name'] = $name;
        $query['addr'] = $new_adress;
        $query['clientid'] = $new_clientid;
        $query['newname'] = $new_name;

        if ($srv) {
            $query['srv'] = $srv;
        }

        $client = new Client();
        try {
            $response = $client->request(
                'GET',
                $url .'/control/redirect/'.$type,
                [
                    'query' => $query
                ]
            );
        } catch (RequestException $e) {
            return false;
        }
        catch (Exception $e) {
            return false;
        }

        return true;
    }
}
