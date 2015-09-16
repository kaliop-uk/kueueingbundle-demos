<?php

namespace Kaliop\Queueing\Demos\UrlCheckerBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Table()
 * @ORM\Entity
 */
class CheckedUrl
{
    /**
     * @ORM\Column(length=64)
     * @ORM\Id
     * @ORM\generatedValue(strategy="NONE")
     */
    protected $urlmd5;

    /**
     * NB: max PK length varies depending on DB. For mysql 5.5 it is 767 bytes.
     * Thus we can not make URL the PK...
     *
     * @ORM\Column(length=4000)
     */
    protected $url;

    /**
     * @ORM\Column(type="decimal", precision=32, scale=3)
     */
    protected $checkDate;

    /**
     * @ORM\Column(type="integer")
     */
    protected $status;

    /**
     * @param string $url
     * @param int $status
     * @param float $time unix time with milliseconds as fractional part
     */
    public function __construct($url, $status, $time=null)
    {
        if ($time == null) {
            $time = microtime(true);
        }

        $this->setUrl($url);
        $this->status = $status;
        $this->checkDate = $time;
    }

    /*public function getUrlmd5()
    {
        return $this->urlmd5;
    }*/

    public function setUrl($url)
    {
        $this->url = $url;
        $this->urlmd5 = md5($url);

        return $this;
    }

    public function setStatus($status)
    {
        $this->status = $status;
    }

    public function setCheckDate($timestamp)
    {
        $this->checkDate = $timestamp;
    }
}
