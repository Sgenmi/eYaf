<?php

/**
 * Created by IntelliJ IDEA.
 * Author: sgenmi
 * Date: 2022/11/24 1:48 PM
 * Email: 150560159@qq.com
 */

namespace Sgenmi\eYaf\Utility;

class Curl
{
    private string $curl_ua = "okhttp/3.10.0.1";
    private array $curl_header = [];
    private string $curl_referer = '';
    private string $curl_cookie = '';
    private string $proxy_ip = '';
    private int $proxy_port = 0;
    private int $timeout = 6;

    /**
     * @param string $curl_ua
     */
    public function setUa(string $curl_ua): void
    {
        $this->curl_ua = $curl_ua;
    }

    /**
     * @param array $curl_header
     */
    public function setHeader(array $curl_header): void
    {
        $this->curl_header = $curl_header;
    }

    /**
     * @param string $curl_referer
     */
    public function setReferer(string $curl_referer): void
    {
        $this->curl_referer = $curl_referer;
    }

    /**
     * @param string $curl_cookie
     */
    public function setCookie(string $curl_cookie): void
    {
        $this->curl_cookie = $curl_cookie;
    }

    /**
     * @param string $proxy_ip
     */
    public function setProxyIp(string $proxy_ip): void
    {
        $this->proxy_ip = $proxy_ip;
    }

    /**
     * @param int $proxy_port
     */
    public function setProxyPort(int $proxy_port): void
    {
        $this->proxy_port = $proxy_port;
    }

    /**
     * @param int $timeout
     */
    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * @param string $url
     * @param mixed $keysArr
     * @param string $method
     * @param bool $return_header
     * @param bool $flag
     * @return array|bool|string
     */
    public function request(string $url, $keysArr = [], string $method = 'get', bool $return_header = false, bool $flag = false)
    {
        $ch = curl_init();
        if (!$flag) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if (strtolower($method) == 'post') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);
        } elseif (strtolower($method) == 'put') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"PUT");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);//设置提交的字符串
        } elseif (strtolower($method) == 'options') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST,"OPTIONS");
            curl_setopt($ch, CURLOPT_POSTFIELDS, $keysArr);//设置提交的字符串
        } else {
            if(strpos($url,'?')!==false){
                if($keysArr){
                    $url = $url . "&" . http_build_query($keysArr);
                }
            }else{
                if($keysArr){
                    $url = $url . "?" . http_build_query($keysArr);
                }
            }
        }

        if ($this->curl_cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $this->curl_cookie);
        }
        if ($return_header) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($this->curl_ua) {
            curl_setopt($ch, CURLOPT_USERAGENT, $this->curl_ua);
        }
        if ($this->curl_referer) {
            curl_setopt($ch, CURLOPT_REFERER, $this->curl_referer);
        }
        if ($this->proxy_ip && $this->proxy_port) {
            curl_setopt($ch, CURLOPT_PROXY, $this->proxy_ip);
            curl_setopt($ch, CURLOPT_PROXYPORT, $this->proxy_port);
        }
        if ($this->curl_header) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $this->curl_header);
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        $ret = curl_exec($ch);

        if ($return_header) {
            if ($this->proxy_ip && $this->proxy_port) {
                if (strpos($ret, 'Connection established') !== false) {
                    list($proxy_header, $header, $content) = explode("\r\n\r\n", $ret);
                    $ret = ['header' => $header, 'data' => $content];
                } else {
                    if ($ret) {
                        list($header, $content) = explode("\r\n\r\n", $ret);
                        $ret = ['header' => $header, 'data' => $content];
                    }
                }
            } else {
                list($header, $content) = explode("\r\n\r\n", $ret);
                $ret = ['header' => $header, 'data' => $content];
            }
        }
        curl_close($ch);
        return $ret;
    }

}