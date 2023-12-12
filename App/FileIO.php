<?php

namespace App;

if(!defined('STDIN'))
    define('STDIN',  fopen('php://stdin',  'rb'));

if(!defined('STDOUT'))
    define('STDOUT', fopen('php://stdout', 'wb'));

if(!defined('STDERR'))
    define('STDERR', fopen('php://stderr', 'wb'));

class FileIO
{

    protected $curl = null;
    protected $cookie_jar = '';
    protected $headers = array();
    protected $response_headers = null;
    protected $url = '';

    public function __construct()
    {
        try {
            $this->curl = curl_init();
        } catch (\RuntimeException $e) {
            echo 'Failed to init! Error is ' . $e->getMessage();
            die;
        }

    }

    public function init(): bool {
        curl_setopt($this->curl, CURLOPT_ENCODING, '');
        curl_setopt($this->curl, CURLOPT_FILETIME, true);
        curl_setopt($this->curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($this->curl, CURLOPT_HEADERFUNCTION, array($this, 'processReadHeaders'));

        $this->setMaxredirs();
        $this->setFollowlocation();
        $this->setVerbose();

        return true;
    }

    public function __destruct()
    {
        curl_close($this->curl);

        if (is_file($this->cookie_jar)) {
            unlink($this->cookie_jar);
        }
    }

    public function setUrl(string $url): void
    {
        if (!parse_url($url))
            throw new \ValueError('Failed to parse url!');

        $this->url = $url;
    }

    public function setCookie(array $cookie): void
    {
        $this->cookie_jar = $cookie;
    }

    public function setUseragent(?string $useragent = null): void
    {
        $useragents = [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.34 Safari/537.36 Edg/83.0.478.25',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.135 YaBrowser/21.6.2.854 Yowser/2.5 Safari/537.36',
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.164 Safari/537.36 OPR/77.0.4054.277',
            'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.185 YaBrowser/20.11.2.80 Yowser/2.5 Safari/537.36'
        ];
        if ($useragent === null) {
            $useragent = array_rand($useragents);
        }

        curl_setopt($this->curl, CURLOPT_USERAGENT, $useragent);
    }

    public function setHeaders(array $headers): bool
    {
        if (isset($headers) && is_array($headers))
            array_walk($headers, function (&$item, $key) {
                if (!is_numeric($key))
                    $item = $key . ": " . $item;
            });

            if (isset($headers) && !empty($headers)) {
                $this->headers = $headers;
                curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);

                return true;
            }

            return false;
    }

    public function modifiedAt(string $filename): ?int
    {
        if (!$this->isFile($filename))
            return null;

        return filemtime($filename);
    }

    public function createdAt(string $filename): ?int
    {
        if (!$this->isFile($filename))
            return null;

        return filectime($filename);
    }

    protected function processReadHeaders(mixed $curl, mixed $header): int
    {
        $values = array_map('trim', explode(':', $header));
        if (isset($values[1])) {
            switch ($values[0]) {
                case 'Content-disposition':
                case 'content-disposition':
                    $values[1] = explode(';', $values[1]);
                    foreach ($values[1] as $item => $value) {
                        $value = explode('=', trim($value));
                        if (isset($value[1])) {
                            unset($values[1][$item]);
                            $values[1][trim($value[0])] = trim($value[1]);
                        }
                    }
            }
            $this->response_headers[$values[0]] = $values[1];
        } elseif ($values[0]) {
            $this->response_headers[] = $values[0];
        }

        return strlen($header);
    }

    public function setAuth(?string $user = null, ?string $pass = null, ?string $auth = null): void
    {
        if (empty($auth))
            $auth = CURLAUTH_ANY;

        curl_setopt($this->curl, CURLOPT_HTTPAUTH, $auth);
        curl_setopt($this->curl, CURLOPT_USERPWD, $user . ':' . $pass);
    }

    public function setSslVerifypeer(bool $ssl_verifypeer = false): void
    {
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYPEER, $ssl_verifypeer);
    }

    public function setSslVerifyhost(bool $ssl_verifyhost = false): void
    {
        curl_setopt($this->curl, CURLOPT_SSL_VERIFYHOST, $ssl_verifyhost);
    }

    public function setVerbose(bool $verbose = true): void
    {
        curl_setopt($this->curl, CURLOPT_VERBOSE, (bool)$verbose);
    }

    public function setFollowlocation(bool $follow = true): void
    {
        curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, (bool)$follow);
    }

    public function setMaxredirs(int $maxredirs = 3): void
    {
        curl_setopt($this->curl, CURLOPT_MAXREDIRS, (int)$maxredirs);
    }

    public function setProxy(?string $ip = null, ?int $port = null, ?string $type = 'CURLPROXY_HTTP', ?string $user = null, ?string $pass = null): void
    {
        curl_setopt($this->curl, CURLOPT_PROXY, $ip);
        curl_setopt($this->curl, CURLOPT_PROXYPORT, $port);
        curl_setopt($this->curl, CURLOPT_PROXYTYPE, $type);
        curl_setopt($this->curl, CURLOPT_PROXYUSERPWD, $user . ':' . $pass);
    }


    public function urlInfo(string $url, ?string $referer = null): array
    {
        $this->response_headers = null;
        $curl_info = [];
        $body = '';

        curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        curl_setopt($this->curl, CURLOPT_URL, $url);
        curl_setopt($this->curl, CURLOPT_REFERER, $referer);
        curl_setopt($this->curl, CURLOPT_NOBODY, true);
        curl_setopt($this->curl, CURLOPT_FILE, STDOUT);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, true);
        $body = curl_exec($this->curl);
        $curl_info = curl_getinfo($this->curl);
        $curl_info['body'] = $body;
        $curl_info['curl_errno'] = curl_errno($this->curl);
        $curl_info['curl_strerror'] = curl_strerror($curl_info['curl_errno']);
        $curl_info['headers'] = $this->response_headers;

        return $curl_info;
    }


    public function createDir(string $dir, int $perms = 0755, bool $die = false): bool
    {
        if (!is_dir($dir)) {
            if (!mkdir(dirname($dir), $perms, true)) {
                if ($die)
                    throw new \RuntimeException('Could not to create a dir ' . $dir);
                else
                    return false;
            }
        }

        return true;
    }

    public function createFile(string $filename): bool|null {

        if ($this->isFile($filename))
            $file = pathinfo($filename);
        else
            throw new \RuntimeException('File ' . $filename . ' are not exists!');

        $this->createDir($file['dirname']);

        if (touch($filename))
            return false;

        return true;
    }

    public static function isFile(string $filename): bool
    {
        return file_exists($filename);
    }

    private function afterDownload(array $result): bool {
        if ($result['http_code'] != 200) {
            throw new \RuntimeException('Server return fault code ' . (int)$result['http_code']);
        }

        if ($result['size_download'] < 1000) {
            throw new \RuntimeException('Download size is too small (less than 1Kb). Current size is ' . (int)$result['http_code']);
        }

        if ($result['curl_errno'] > 0) {
            throw new \RuntimeException('Curl return error ' . (int)$result['curl_errno'] . '. Error text is ' . $result['curl_strerror']);
        }

        return true;
    }

    public function getFileFromUrl(?string $filename = null, ?string $referer = null): bool
    {
        $this->response_headers = null;
        $curl_info = [];

        if (empty($filename))
            $filename = parse_url($this->url, PHP_URL_HOST) . parse_url($this->url, PHP_URL_PATH);

        curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        curl_setopt($this->curl, CURLOPT_URL, $this->url);
        curl_setopt($this->curl, CURLOPT_REFERER, $referer);
        curl_setopt($this->curl, CURLOPT_NOBODY, false);
        curl_setopt($this->curl, CURLOPT_RETURNTRANSFER, false);

        $this->createFile($filename);

        $file_handle = fopen($filename, 'w');

        curl_setopt($this->curl, CURLOPT_FILE, $file_handle);
        curl_exec($this->curl);

        fclose($file_handle);

        $curl_info = curl_getinfo($this->curl);

        $curl_info['filename'] = $filename;
        $curl_info['curl_errno'] = curl_errno($this->curl);
        $curl_info['curl_strerror'] = curl_strerror($curl_info['curl_errno']);
        $curl_info['headers'] = $this->response_headers;

        $this->afterDownload($curl_info);

        return true;
    }



}