<?php
/**
 * @author    jan huang <bboyjanhuang@gmail.com>
 * @copyright 2016
 *
 * @link      https://www.github.com/janhuang
 * @link      http://www.fast-d.cn/
 */

namespace Spider;

use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Uri;
use Item;
use Pipeline;
use Psr\Http\Message\ResponseInterface;
use State\Tying;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;


/**
 * Class SpiderWeb
 * @package Spider
 */
abstract class SpiderWeb
{
    /**
     * @var string
     */
    public $dir;

    /**
     * @var Uri
     */
    public $uri;

    /**
     * @var string
     */
    public $method = 'GET';

    /**
     * @var array
     */
    public $options = [];

    /**
     * @var SpiderWeb[]
     */
    public $emits = [];

    /**
     * @var Crawler
     */
    public $node;

    /**
     * @var ResponseInterface
     */
    public $response;

    /**
     * @var ProgressBar
     */
    public $progress;

    /**
     * @var int
     */
    public $success = 0;

    /**
     * @var int
     */
    public $error = 0;

    public $state;

    /**
     * SpiderWeb constructor.
     * @param string $method
     * @param string $uri
     * @param $options
     */
    public function __construct($method = 'GET', $uri = '', array $options = [])
    {
        $this->method = $method;

        $this->options = $options;

        if ($uri instanceof Uri) {
            $this->uri = $uri;
        } else if($uri instanceof Link) {
            $this->uri = new Uri($uri->getUri());
        } else {
            $this->uri = new Uri($uri);
        }

        $this->state = Tying::$tying;
    }

    /**
     * @param $pipe
     * @param $filter
     * @return $this
     */
    public function pipe($pipe, $filter = '')
    {
        if (is_string($pipe)) {
            if (!class_exists($pipe)) {
                throw new \LogicException(sprintf('Pipe %s is undefined', $pipe));
            }
            $pipe = new $pipe($this);
        }

        if ($pipe instanceof Pipeline) {
            $pipe->setItem(new Item());
        }

        if (!empty($filter)) {
            // XPath
            if ('//' == substr($filter, 0, 2)) {
                $node = $this->node->filterXPath($filter);
            } else {
                $node = $this->node->filter($filter);
            }
        } else {
            $node = $this->node;
        }

        call_user_func_array([$pipe, 'processItem'], [$node, clone $this->response]);

        return $this;
    }

    /**
     * @param SpiderWeb $spiderWeb
     * @return $this
     */
    public function emit(SpiderWeb $spiderWeb)
    {
        $spiderWeb->options = array_merge($this->options, $spiderWeb->options);

        $this->emits[] = $spiderWeb;

        return $this;
    }

    /**
     * @param $name
     * @return mixed
     */
    public function state($name)
    {
        return Tying::$tying[$name];
    }

    /**
     * @param $step
     * @return $this
     */
    public function setMaxProgress($step)
    {
        $this->progress->setProgress($step);

        return $this;
    }

    /**
     * @param $step
     * @return $this
     */
    public function setStartProgress($step)
    {
        $this->progress->setProgress($step);

        return $this;
    }

    /**
     * @param Crawler $crawler
     * @param ResponseInterface $response
     * @return mixed
     */
    abstract function process(Crawler $crawler, ResponseInterface $response);

    /**
     * @param RequestException $requestException
     * @return mixed
     */
    abstract function error(RequestException $requestException);
}