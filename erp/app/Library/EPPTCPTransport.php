<?php

namespace App\Library;
class EPPTransportError extends \Exception{ };

class EPPTCPTransport
{
    /*
       An epp client transport class. This is just the raw TCP IP protocol. The XML data needs to be handled separatly.
       The EPP transport protocol is definied at http://tools.ietf.org/html/rfc5734 it looks complicated but is
       actually very simple and elegant.
       the actual data should be XML data formated according to RFC5730-RFC5733
       No validation of any data takes place in this Class
     */

    public $sock;
    public $greeting; #Contents of the epp greeting when we connect

    public function __construct($server, $port)
    {
        $context = stream_context_create();
        #http://bytes.com/topic/php/answers/389749-fsockopen-php5-missing-6th-argument-context
        //  stream_context_set_option($context, 'ssl', 'capath', '/portal/ssl/letsencrypt');
        //  stream_context_set_option($context, 'ssl', 'cafile', '/portal/ssl/letsencrypt/fullchain.pem');
        stream_context_set_option($context, 'ssl', 'local_cert', env('APP_URL').'/zacr_ssl/epp.pem');
        stream_context_set_option($context, 'ssl', 'verify_peer', false);
        stream_context_set_option($context, 'ssl', 'verify_host', false);
        stream_context_set_option($context, 'ssl', 'allow_self_signed', true);

        $this->socket = stream_socket_client("tls://".$server.":".$port, $errno, $errstr, 60, STREAM_CLIENT_CONNECT, $context);
        if ($this->socket === false) {
            throw new Exception("Error connecting to $target: $errstr (code $errno)");
        }
        $this->greeting=$this->read();
    }

    /* Read an EPP XML Instance
     */

    public function read()
    {
        $headerstr = "";
        while (strlen($headerstr) < 4) {
            $lenstr = fread($this->socket, 4 - strlen($headerstr)); #Get the size of the complete EPP Data Unit
            if ($lenstr === false) {
                throw new EPPTransportError("Socket error");
            }
            $headerstr = $headerstr . $lenstr;
        }
        $bytes = unpack("Nbytes", $headerstr);
        $bytes = $bytes['bytes']; #PHP unpack returns an assoc array
        $data = "";
        while (strlen($data) < ($bytes - 4)) {
            $tmpdata = fread($this->socket, ($bytes-4) - strlen($data)); #1st 4 bytes have already been read
            if ($tmpdata ===false) {
                throw new EPPTransportError("Didn't recieve any XML data?");
            }
            $data = $data . $tmpdata;
        }
        return $data;
    }

    public function write($eppmsg)
    {
        $lenstr=pack("N", strlen($eppmsg)+4);
        fwrite($this->socket, $lenstr);
        fwrite($this->socket, $eppmsg);
        return;
    }

    /*
       Convenience function. Does a write followed by a read
     */
    public function chat($eppmsg)
    {
        $this->write($eppmsg);
        return $this->read();
    }

    /**
    * Close the connection.
    */
    public function close()
    {
        return @fclose($this->socket);
    }
}

