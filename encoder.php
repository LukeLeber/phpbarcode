<?php

    namespace com\github\lukeleber\phpbarcode\detail;

    class EncoderException extends \Exception
    {
        
    }

    interface Encoder
    {
        public function encode($text);
        public function decode($binary);
    }

    $encoders = array();

    function register_encoder($alias, $encoder)
    {
        $encoders[$alias] = $encoder;
    }

    function get_encoder($alias)
    {
        return $encoders[$alias];
    }


    class DebugEncoder implements Encoder
    {
        public function encode($text)
        {
            
        }
        
        public function decode($binary)
        {
            
        }
    }

    register_encoder("debug", new DebugEncoder());
