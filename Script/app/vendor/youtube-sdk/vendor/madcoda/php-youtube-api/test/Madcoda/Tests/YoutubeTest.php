<?php

namespace Madcoda\Tests;

require_once(__DIR__ . '/../../../vendor/autoload.php');

use Madcoda\Youtube;

/**
 * Class YoutubeTest
 *
 * @category Youtube
 * @package  Youtube
 * @author   Jason Leung <jason@madcoda.com>
 */
class YoutubeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Youtube
     */
    protected $youtube;

    public function setUp()
    {
        $TEST_API_KEY = 'AIzaSyDDefsgXEZu57wYgABF7xEURClu4UAzyB8';
        $this->youtube = new Youtube(array('key' => $TEST_API_KEY));
    }

    public function tearDown()
    {
        $this->youtube = null;
    }

    public function MalFormURLProvider()
    {
        return array(
            array('https://'),
            array('http://www.yuotube.com'),
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorFail()
    {
        $this->youtube = new Youtube(array());
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testConstructorFail2()
    {
        $this->youtube = new Youtube('FAKE API KEY');
    }

    /**
     * @expectedException \Exception
     * @expectedExceptionMessage    Error 400 Bad Request : keyInvalid
     */
    public function testInvalidApiKey()
    {
        $this->youtube = new Youtube(array('key' => 'nonsense'));
        $vID = 'rie-hPVJ7Sw';
        $this->youtube->getVideoInfo($vID);
    }

    public function testGetVideoInfo()
    {
        $vID = 'rie-hPVJ7Sw';
        $response = $this->youtube->getVideoInfo($vID);

        $this->assertEquals($vID, $response->id);
        $this->assertNotNull('response');
        $this->assertEquals('youtube#video', $response->kind);
        //add all these assertions here in case the api is changed,
        //we can detect it instantly
        $this->assertObjectHasAttribute('statistics', $response);
        $this->assertObjectHasAttribute('status', $response);
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectHasAttribute('contentDetails', $response);
    }

    public function testGetVideosInfo()
    {
        $vID = array('rie-hPVJ7Sw', 'lRRk97FYLJM');
        $response = $this->youtube->getVideosInfo($vID);
        $this->assertInternalType('array', $response);
        
        foreach ($response as $value) {
            $this->assertContains($value->id, $vID);
            $this->assertEquals('youtube#video', $value->kind);
            //add all these assertions here in case the api is changed,
            //we can detect it instantly
            $this->assertObjectHasAttribute('statistics', $value);
            $this->assertObjectHasAttribute('status', $value);
            $this->assertObjectHasAttribute('snippet', $value);
            $this->assertObjectHasAttribute('contentDetails', $value);
        }
    }

    public function testSearch()
    {
        $limit = rand(3, 10);
        $response = $this->youtube->search('Android', $limit);
        $this->assertEquals($limit, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
    }

    public function testSearchVideos()
    {
        $limit = rand(3, 10);
        $response = $this->youtube->searchVideos('Android', $limit);
        $this->assertEquals($limit, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
        $this->assertEquals('youtube#video', $response[0]->id->kind);
    }

    public function testSearchChannelVideos()
    {
        $limit = rand(3, 10);
        $response = $this->youtube->searchChannelVideos('Android', 'UCVHFbqXqoYvEWM1Ddxl0QDg', $limit);
        $this->assertEquals($limit, count($response));
        $this->assertEquals('youtube#searchResult', $response[0]->kind);
        $this->assertEquals('youtube#video', $response[0]->id->kind);
    }

    public function testSearchAdvanced()
    {
        //TODO
    }

    public function testGetChannelByName()
    {
        $response = $this->youtube->getChannelByName('Google');

        $this->assertEquals('youtube#channel', $response->kind);
        //This is not a safe Assertion because the name can change, but include it anyway
        $this->assertEquals('Google', $response->snippet->title);
        //add all these assertions here in case the api is changed,
        //we can detect it instantly
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectHasAttribute('contentDetails', $response);
        $this->assertObjectHasAttribute('statistics', $response);
    }

    public function testGetChannelById()
    {
        $channelId = 'UCk1SpWNzOs4MYmr0uICEntg';
        $response = $this->youtube->getChannelById($channelId);

        $this->assertEquals('youtube#channel', $response->kind);
        $this->assertEquals($channelId, $response->id);
        $this->assertObjectHasAttribute('snippet', $response);
        $this->assertObjectHasAttribute('contentDetails', $response);
        $this->assertObjectHasAttribute('statistics', $response);
    }

    public function testGetPlaylistsByChannelId()
    {
        $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $response = $this->youtube->getPlaylistsByChannelId($GOOGLE_CHANNELID);

        $this->assertTrue(count($response) > 0);
        $this->assertEquals('youtube#playlist', $response[0]->kind);
        $this->assertEquals('Google', $response[0]->snippet->channelTitle);
    }

    public function testGetPlaylistById()
    {
        //get one of the playlist
        $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $response = $this->youtube->getPlaylistsByChannelId($GOOGLE_CHANNELID);
        $playlist = $response[0];

        $response = $this->youtube->getPlaylistById($playlist->id);
        $this->assertEquals('youtube#playlist', $response->kind);
    }

    public function testGetPlaylistItemsByPlaylistId()
    {
        $GOOGLE_ZEITGEIST_PLAYLIST = 'PL590L5WQmH8fJ54F369BLDSqIwcs-TCfs';
        $response = $this->youtube->getPlaylistItemsByPlaylistId($GOOGLE_ZEITGEIST_PLAYLIST);

        $this->assertTrue(count($response) > 0);
        $this->assertEquals('youtube#playlistItem', $response[0]->kind);
    }

    public function testParseVIdFromURLFull()
    {
        $vId = $this->youtube->parseVIdFromURL('http://www.youtube.com/watch?v=1FJHYqE0RDg');
        $this->assertEquals('1FJHYqE0RDg', $vId);
    }

    public function testParseVIdFromURLShort()
    {
        $vId = $this->youtube->parseVIdFromURL('http://youtu.be/1FJHYqE0RDg');
        $this->assertEquals('1FJHYqE0RDg', $vId);
    }

    /**
     *
     * @dataProvider MalFormURLProvider
     * @expectedException \Exception
     */
    public function testParseVIdFromURLException($url)
    {
        $vId = $this->youtube->parseVIdFromURL($url);
    }

    /**
     * @expectedException \Exception
     */
    public function testParseVIdException()
    {
        $vId = $this->youtube->parseVIdFromURL('http://www.facebook.com');
    }

    public function testGetActivitiesByChannelId()
    {
        $GOOGLE_CHANNELID = 'UCK8sQmJBp8GCxrOtXWBpyEA';
        $response = $this->youtube->getActivitiesByChannelId($GOOGLE_CHANNELID);
        $this->assertTrue(count($response) > 0);
        $this->assertEquals('youtube#activity', $response[0]->kind);
        $this->assertEquals('Google', $response[0]->snippet->channelTitle);
    }

    /**
     * @expectedException  \InvalidArgumentException
     */
    public function testGetActivitiesByChannelIdException()
    {
        $channelId = '';
        $response = $this->youtube->getActivitiesByChannelId($channelId);
    }

    public function testGetChannelFromURL()
    {
        $channel = $this->youtube->getChannelFromURL('http://www.youtube.com/user/Google');

        $this->assertEquals('UCK8sQmJBp8GCxrOtXWBpyEA', $channel->id);
        $this->assertEquals('Google', $channel->snippet->title);
    }

    /**
     * Test skipped for now, since the API returns Error 500
     */
    public function testNotFoundAPICall()
    {
        $vID = 'Utn7NBtbHL4'; //an deleted video
        $response = $this->youtube->getVideoInfo($vID);
        $this->assertFalse($response);
    }

    /**
     * Test skipped for now, since the API returns Error 500
     */
    public function testNotFoundAPICall2()
    {
        //$channelId = 'non_exist_channelid';
        //$response = $this->youtube->getPlaylistsByChannelId($channelId);
        //$this->assertFalse($response);
    }
}