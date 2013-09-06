<?php
/**
 * TestEmbera.php
 *
 * @package Tests
 * @author Michael Pratt <pratt@hablarmierda.net>
 * @link   http://www.michael-pratt.com/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

class TestEmbera extends PHPUnit_Framework_TestCase
{
    public function testInvalidAutoEmbedInput()
    {
        $input = array('http://www.youtube.com/watch?v=GP18O6gSWSw&feature=share&list=PL4EF7BAD98F9812B6');
        $embera = new \Embera\Embera();

        $this->assertEquals($input, $embera->autoEmbed($input));
        $this->assertTrue($embera->hasErrors());
        $this->assertTrue(is_string($embera->getLastError()));
        $this->assertCount(1, $embera->getErrors());
    }

    public function testInvalidAutoEmbedInput2()
    {
        $input = null;
        $embera = new \Embera\Embera();

        $this->assertEquals($input, $embera->autoEmbed($input));
        $this->assertTrue(is_string($embera->getLastError()));
        $this->assertTrue($embera->hasErrors());
        $this->assertCount(1, $embera->getErrors());
    }

    public function testAutoEmbedwithoutKnownServices()
    {
        $input = 'hola este texto debería seguir igual.';
        $embera = new \Embera\Embera();

        $this->assertEquals($input, $embera->autoEmbed($input));
    }

    public function testAutoEmbedwithoutKnownServices2()
    {
        $input = 'hola este texto debería seguir igual. http://www.google.com ';
        $embera = new \Embera\Embera();

        $this->assertEquals($input, $embera->autoEmbed($input));
    }

    public function testFakeUrlInspection()
    {
        $validUrls = array('http://www.youtube.com/watch?v=MpVHQnIvTXo',
                           'http://youtu.be/fSUK4WgQ3vk',
                           'http://www.youtube.com/watch?v=T3O1nffTG-k');

        $embera = new \Embera\Embera(array('oembed' => false));
        $result = $embera->getUrlInfo($validUrls);

        $this->assertCount(count($validUrls), $result);
    }

    public function testFakeAutoEmbed()
    {
        $text = 'Hey Checkout this video http://www.youtube.com/watch?v=MpVHQnIvTXo, Its just great';
        $embera = new \Embera\Embera(array('oembed' => false));
        $result = $embera->autoEmbed($text);

        $this->assertContains('<iframe', $result);
        $this->assertEmpty($embera->getLastError());
    }

    public function testCustomProvider()
    {
        $oembed = new MockOembed(true, new MockHttpRequest());
        $customParams = array('apikey' => 'myapikey');
        $urls = array(
            'http://customservice.com/9879837498',
            'http://host.com/stuff/yes',
            'http://customservice.com/hi',
            'http://www.customservice.com/98756478',
            'http://customservice.com/9879837498/'
        );

        $p = new \Embera\Providers(array(), $oembed);
        $p->addProvider('www.customservice.com', 'CustomService', $customParams);

        $this->assertCount(3, $p->getAll($urls));

        $all = $p->getAll($urls);
        foreach ($all as $s) {
            $params = array_filter($s->getParams());
            $this->assertEquals($params, $customParams);
        }
    }

    public function testCustomProvider2()
    {
        $urls = array(
            'http://customservice.com/9879837498',
            'http://host.com/stuff/yes',
            'http://customservice.com/hi',
            'http://www.customservice.com/98756478',
            'http://customservice.com/9879837498/'
        );

        $embera = new \Embera\Embera();
        $embera->addProvider('www.customservice.com', 'CustomService', array());

        $reflection = new ReflectionClass('\Embera\Embera');
        $method = $reflection->getMethod('getProviders');
        $method->setAccessible(true);

        $providers = $method->invoke($embera, $urls);
        $this->assertCount(3, $providers);
    }

    public function testCustomFakeParams()
    {
        $config = array(
            'oembed' => false,
            'fake' => array(
                'width' => 400,
                'height' => 500,
            )
        );

        $embera = new \Embera\Embera($config);

        $reflection = new ReflectionClass('\Embera\Embera');
        $method = $reflection->getMethod('getProviders');
        $method->setAccessible(true);

        $providers = $method->invoke($embera, array(
            'http://youtu.be/fSUK4WgQ3vk',
            'http://vimeo.com/groups/shortfilms/videos/63313811/',
        ));

        $this->assertCount(2, $providers);

        foreach($providers as $p)
        {
            $reflection = new ReflectionClass($p);
            $method = $reflection->getMethod('getWidth');
            $method->setAccessible(true);

            $this->assertEquals($method->invoke($p), 400);

            $reflection = new ReflectionClass($p);
            $method = $reflection->getMethod('getHeight');
            $method->setAccessible(true);

            $this->assertEquals($method->invoke($p), 500);
        }
    }

    public function testCustomFakeParams2()
    {
        $config = array(
            'oembed' => false,
            'fake' => array(
                'height' => 500,
            )
        );

        $embera = new \Embera\Embera($config);

        $reflection = new ReflectionClass('\Embera\Embera');
        $method = $reflection->getMethod('getProviders');
        $method->setAccessible(true);

        $providers = $method->invoke($embera, array(
            'http://www.flickr.com/photos/reddragonflydmc/5427387397/',
        ));

        $this->assertCount(1, $providers);

        foreach($providers as $p)
        {
            $reflection = new ReflectionClass($p);
            $method = $reflection->getMethod('getWidth');
            $method->setAccessible(true);

            // 420 is the default fake width
            $this->assertEquals($method->invoke($p), 420);

            $reflection = new ReflectionClass($p);
            $method = $reflection->getMethod('getHeight');
            $method->setAccessible(true);

            $this->assertEquals($method->invoke($p), 500);
        }
    }

    public function testUrlString()
    {
        $validUrls = 'Hey what up! http://www.youtube.com/watch?v=MpVHQnIvTXo this is great http://youtu.be/fSUK4WgQ3vk';

        $embera = new \Embera\Embera(array('oembed' => false));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount(2, $result);
    }

    public function testCustomParams()
    {
        $config = array(
            'custom_params' => array(
                'Youtube' => array('custom' => 'none'),
                'vimeO' => array('apikey' => '8987928734234')
            )
        );

        $embera = new \Embera\Embera($config);

        $reflection = new ReflectionClass('\Embera\Embera');
        $method = $reflection->getMethod('getProviders');
        $method->setAccessible(true);

        $providers = $method->invoke($embera, array(
            'http://stuff.com/unknown',
            'http://youtu.be/fSUK4WgQ3vk',
            'http://www.youtube.com/watch?v=MpVHQnIvTXo',
            'http://vimeo.com/groups/shortfilms/videos/63313811/',
            'http://www.dailymotion.com/video/xzxtaf_red-bull-400-alic-y-stadlober-ganan-en-eslovenia_sport/',
        ));

        $this->assertCount(4, $providers);

        foreach($providers as $p)
        {
            $class = strtolower(basename(str_replace('\\', '/', get_class($p))));
            $params = array_filter($p->getParams());

            if ($class == 'youtube')
                $this->assertEquals($params, $config['custom_params']['Youtube']);
            else if ($class == 'vimeo')
                $this->assertEquals($params, $config['custom_params']['vimeO']);
            else
                $this->assertTrue(empty($params), 'Param array for ' .  $class . ' - ' . print_r($params, true));
        }
    }

    public function testDenyService()
    {
        $validUrls = array('http://www.youtube.com/watch?v=MpVHQnIvTXo',
                           'http://youtu.be/fSUK4WgQ3vk',
                           'http://vimeo.com/groups/shortfilms/videos/63313811/',
                           'http://www.youtube.com/watch?v=T3O1nffTG-k');

        $embera = new \Embera\Embera(array('oembed' => false, 'deny' => array('Youtube')));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount(1, $result);

        $embera = new \Embera\Embera(array('oembed' => false, 'deny' => array('youTube')));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount(1, $result);
    }

    public function testAllowService()
    {
        $validUrls = array('http://www.youtube.com/watch?v=MpVHQnIvTXo',
                           'http://youtu.be/fSUK4WgQ3vk',
                           'http://vimeo.com/groups/shortfilms/videos/63313811/',
                           'http://www.dailymotion.com/video/xzxtaf_red-bull-400-alic-y-stadlober-ganan-en-eslovenia_sport/',
                           'http://www.youtube.com/watch?v=T3O1nffTG-k');

        $embera = new \Embera\Embera(array('oembed' => false, 'allow' => array('Vimeo', 'dailyMotion')));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount(2, $result);

        $embera = new \Embera\Embera(array('oembed' => false, 'allow' => array('Youtube', 'Dailymotion')));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount((count($validUrls) - 1), $result);
    }

    public function testEmbedPrefixService()
    {
        $validUrls = array('embed://www.youtube.com/watch?v=MpVHQnIvTXo',
                           'http://youtu.be/fSUK4WgQ3vk',
                           'http://vimeo.com/groups/shortfilms/videos/63313811/',
                           'embed://www.dailymotion.com/video/xzxtaf_red-bull-400-alic-y-stadlober-ganan-en-eslovenia_sport/',
                           'embed://www.youtube.com/watch?v=T3O1nffTG-k');

        $embera = new \Embera\Embera(array('oembed' => false, 'use_embed_prefix' => false));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount(2, $result);

        $embera = new \Embera\Embera(array('oembed' => false, 'use_embed_prefix' => true));
        $result = $embera->getUrlInfo($validUrls);
        $this->assertCount(3, $result);
    }
}

?>
