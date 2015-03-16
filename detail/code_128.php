<?php

    namespace com\github\lukeleber\phpbarcode\detail;

    require_once(__DIR__ . '/../encoder.php');

    /**
     * Well, I picked the most difficult 2D encoding algorithm to implement.  
     * I might recall Jesse mentioning that the warehouse uses code-128 for 
     * everything, so now you can pop them out with PHP.
     *
     * Oh, this file can be considered public domain too.  Why not?
     *
     *
     *
     * Disclaimer: Here be dragons.
     * 
     */
    class Code128 implements Encoder
    {
        
        /// Various constants that are explicitly defined by the specification
        
        ///
        /// ASCII Control Characters
        ///
        
        /// Null Control Character
        const NUL = 0x0000;
        
        /// Start of Header Control
        const SOH = 0x0001;
        
        /// Start of Text Control
        const STX = 0x0002;
        
        /// End of Text Control Character
        const ETX = 0x0003;
        
        /// End of Transmission Control Character
        const EOT = 0x0004;
        
        /// Enquiry Control Character
        const ENQ = 0x0005;
        
        /// Acknowledge Control Character
        const ACK = 0x0006;
        
        /// Bell Control Character
        const BEL = 0x0007;
        
        /// BackSpace Control Character
        const BS = 0x0008;
        
        /// Horizontal Tabulation Control Character
        const HT = 0x0009;
        
        /// Line Feed Control Character
        const LF = 0x000A;
        
        /// Vertical Tabulation Control Character
        const VT = 0x000B;
        
        /// Form Feed Control Character
        const FF = 0x000C;
        
        /// Carriage Return Control Character
        const CR = 0x000D;
        
        /// Shift Out Control Character
        const SO = 0x000E;
        
        /// Shift In Control Character
        const SI = 0x000F;
        
        /// Data Link Escape Control Character
        const DLE = 0x0010;
        
        /// Device Control 1 Control Character
        const DC1 = 0x0011;
        
        /// Device Control 2 Control Character
        const DC2 = 0x0012;
        
        /// Device Control 3 Control Character
        const DC3 = 0x0013;
        
        /// Device Control 4 Control Character
        const DC4 = 0x0014;
        
        /// Negative Acknowledge Control Character
        const NAK = 0x0015;
        
        /// Synchronous Idle Control Character
        const SYN = 0x0016;
        
        /// End of Transmission Block Control Character
        const ETB = 0x0017;
        
        /// Cancel Control Character
        const CAN = 0x0018;
        
        /// End of Medium Control Character
        const EM = 0x0019;
        
        /// Substitute Control Character
        const SUB = 0x001A;
        
        /// Escape Control Character
        const ESC = 0x001B;
        
        /// File Separator Control Character
        const FS = 0x001C;
        
        /// Group Separator Control Character
        const GS = 0x001D;
        
        /// Record Separator Control Character
        const RS = 0x001E;
        
        /// Unit Separator Control Character
        const US = 0x001F;
        
        /// Delete Control Character
        const DEL = 0x007F;
        
        ///
        /// The following are not ASCII control characters, but rather 
        /// signals unique to Code-128.  Note: Some variations of this 
        /// symbology may map these signals to different character(s)!
        /// Treat the values as loosely as feasible in the future!
        ///
        
        /// Special Function 1 Signal
        const FNC1 = 0x00CA;
        
        /// Special Function 2 Signal
        const FNC2 = 0x00C5;
        
        /// Special Function 3 Signal
        const FNC3 = 0x00C4;
        
        /// Special Function 4 Signal
        const FNC4 = 0x00C8;
        
        /// Shift Signal
        const SHIFT = 0x00C6;

        ///
        /// Note - The following start / shift characters do not have a printable representation and
        /// the values chosen for them are arbitrary (not defined by any specification / standard).
        /// Having these arbitrary 'sentinels' is useful for injecting optimized shifting
        /// strategies during the encoding process that were not present in the input text.
        ///
        
        /// Arbitrary value for the Mode-A start character
        const START_A = 0x00DA;

        /// Arbitrary value for the Mode-B start character
        const START_B = 0x00DB;
        
        /// Arbitrary value for the Mode-C start character
        const START_C = 0x00DC;
        
        /// Arbitrary value for the "shift-to-mode-a" character
        const SHIFT_A = 0x00DD;
        
        /// Arbitrary value for the "shift-to-mode-b" character
        const SHIFT_B = 0x00DE;
        
        /// Arbitrary value for the "shift-to-mode-c" character
        const SHIFT_C = 0x00DF;
        
        /// The fugly character set for mode A
        const CHARSET_A = " !\"#$%&\'()*.,-./0123456789:;<=>?@" .
            "ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_" . Code128::NUL . Code128::SOH . 
            Code128::STX . Code128::ETX . Code128::EOT . Code128::ENQ . 
            Code128::ACK . Code128::BEL . Code128::BS . Code128::HT . 
            Code128::LF . Code128::VT . Code128::FF . Code128::CR . 
            Code128::SO . Code128::SI . Code128::DLE . Code128::DC1 . 
            Code128::DC2 . Code128::DC3 . Code128::DC4 . Code128::NAK . 
            Code128::SYN . Code128::ETB . Code128::CAN . Code128::EM . 
            Code128::SUB . Code128::ESC . Code128::FS . Code128::GS . 
            Code128::RS . Code128::US . Code128::FNC3 . Code128::FNC2 . 
            Code128::SHIFT . Code128::SHIFT_C . Code128::SHIFT_B . 
            Code128::FNC4 . Code128::FNC1 . Code128::START_A . 
            Code128::START_B . Code128::START_C;

        /// The fugly character set for mode B
        const CHARSET_B = " !\"#$%&\'()*.,-./0123456789:;<=>?@" .
            "ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`" . 
            "abcdefghijklmnopqrstuvwxyz{|}~" . Code128::DEL . Code128::FNC3 . 
            Code128::FNC2 . Code128::SHIFT . Code128::SHIFT_C . 
            Code128::FNC4 . Code128::SHIFT_A . Code128::FNC1 . 
            Code128::START_A . Code128::START_B . Code128::START_C;

        /// The not-quite-so-fugly character set for mode C
        const CHARSET_C = "0123456789" . Code128::FNC1 . Code128::SHIFT_B . 
            Code128::SHIFT_A . Code128::START_A . Code128::START_B . 
            Code128::START_C;

        /// The ultra-fugly code-128 character set to be used for input 
        /// validation. Code128::Essentially, this string is a combination 
        /// of all 3 character modes minus shift and start codes.
        const CHARSET = " !\"#$%&\'()*.,-./0123456789:;<=>?@" .
            "ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_" . Code128::NUL . Code128::SOH . 
            Code128::STX . Code128::ETX . Code128::EOT . Code128::ENQ . 
            Code128::ACK . Code128::BEL . Code128::BS . Code128::HT . 
            Code128::LF . Code128::VT . Code128::FF . Code128::CR . 
            Code128::SO . Code128::SI . Code128::DLE . Code128::DC1 . 
            Code128::DC2 . Code128::DC3 . Code128::DC4 . Code128::NAK . 
            Code128::SYN . Code128::ETB . Code128::CAN . Code128::EM . 
            Code128::SUB . Code128::ESC . Code128::FS . Code128::GS . 
            Code128::RS . Code128::US . Code128::DEL . Code128::FNC1 . 
            Code128::FNC2 . Code128::FNC3 . Code128::FNC4 . Code128::SHIFT;

        ///
        /// Since PHP does not (without a few hacks) support enumerations, 
        /// These fields and a few utility methods were added in leu of the 
        /// deficiency.
        ///
        
        /// Symbolic constant indicating that we are currently using encoder 1
        const MODE_A = 0x0;
        
        /// Symbolic constant indicating that we are currently using encoder 2
        const MODE_B = 0x1;
        
        /// Symbolic constant indicating that we are currently using encoder 3
        const MODE_C = 0x2;
        
        /// The index into the encoder table where the START_A sequence resides
        const START_A_INDEX = 103;
        
        /// The index into the encoder table where the START_B sequence resides
        const START_B_INDEX = 104;
        
        /// The index into the encoder table where the START_C sequence resides
        const START_C_INDEX = 105;
        
        /// The "quiet zone" (which is just a white space)
        const QUIET_ZONE = array(5);
        
        /// The encoding sequence for the START_A symbol
        const START_A_ENCODING = array(2, 1, 1, 4, 1, 2);
        
        /// The encoding sequence for the START_B symbol
        const START_B_ENCODING = array(2, 1, 1, 2, 1, 4);
        
        /// The encoding sequence for the START_C symbol
        const START_C_ENCODING = array(2, 1, 1, 2, 3, 2);
        
        /// The encoding sequence for the STOP symbol
        const STOP_ENCODING = array(2, 3, 3, 1, 1, 1);
        
        /// The encoding sequence for the TERMINATION symbol
        const TERMINATION_ENCODING = array(2);
        
        /// The lookup-table that holds all possible encodings for code-128
        const ENCODING_TABLE = array(
            array(2, 1, 2, 2, 2, 2), // 0
            array(2, 2, 2, 1, 2, 2), // 1
            array(2, 2, 2, 2, 2, 1), // 2
            array(1, 2, 1, 2, 2, 3), // 3
            array(1, 2, 1, 3, 2, 2), // 4
            array(1, 3, 1, 2, 2, 2), // 5
            array(1, 2, 2, 2, 1, 3), // 6
            array(1, 2, 2, 3, 1, 2), // 7
            array(1, 3, 2, 2, 1, 2), // 8
            array(2, 2, 1, 2, 1, 3), // 9
            array(2, 2, 1, 3, 1, 2), // 10
            array(2, 3, 1, 2, 1, 2), // 11
            array(1, 1, 2, 2, 3, 2), // 12
            array(1, 2, 2, 1, 3, 2), // 13
            array(1, 2, 2, 2, 3, 1), // 14
            array(1, 1, 3, 2, 2, 2), // 15
            array(1, 2, 3, 1, 2, 2), // 16
            array(1, 2, 3, 2, 2, 1), // 17
            array(2, 2, 3, 2, 1, 1), // 18
            array(2, 2, 1, 1, 3, 2), // 19
            array(2, 2, 1, 2, 3, 1), // 20
            array(2, 1, 3, 2, 1, 2), // 21
            array(2, 2, 3, 1, 1, 2), // 22
            array(3, 1, 2, 1, 3, 1), // 23
            array(3, 1, 1, 2, 2, 2), // 24
            array(3, 2, 1, 1, 2, 2), // 25
            array(3, 2, 1, 2, 2, 1), // 26
            array(3, 1, 2, 2, 1, 2), // 27
            array(3, 2, 2, 1, 1, 2), // 28
            array(3, 2, 2, 2, 1, 1), // 29
            array(2, 1, 2, 1, 2, 3), // 30
            array(2, 1, 2, 1, 2, 3), // 31
            array(2, 3, 2, 1, 2, 1), // 32
            array(1, 1, 1, 3, 2, 3), // 33
            array(1, 3, 1, 1, 2, 3), // 34
            array(1, 3, 1, 3, 2, 1), // 35
            array(1, 1, 2, 3, 1, 3), // 36
            array(1, 3, 2, 1, 1, 3), // 37
            array(1, 3, 2, 3, 1, 1), // 38
            array(2, 1, 1, 3, 1, 3), // 39
            array(2, 3, 1, 1, 1, 3), // 40
            array(2, 3, 1, 3, 1, 1), // 41
            array(1, 1, 2, 1, 3, 3), // 42
            array(1, 1, 2, 3, 3, 1), // 43
            array(1, 3, 2, 1, 3, 1), // 44
            array(1, 1, 3, 1, 2, 3), // 45
            array(1, 1, 3, 3, 2, 1), // 46
            array(1, 3, 3, 1, 2, 1), // 47
            array(3, 1, 3, 1, 2, 1), // 48
            array(2, 1, 1, 3, 3, 1), // 49
            array(2, 3, 1, 1, 3, 1), // 50
            array(2, 1, 3, 1, 1, 3), // 51
            array(2, 1, 3, 3, 1, 1), // 52
            array(2, 1, 3, 1, 3, 1), // 53
            array(3, 1, 1, 1, 2, 3), // 54
            array(3, 1, 1, 3, 2, 1), // 55
            array(3, 3, 1, 1, 2, 1), // 56
            array(3, 1, 2, 1, 1, 3), // 57
            array(3, 1, 2, 3, 1, 1), // 58
            array(3, 3, 2, 1, 1, 1), // 59
            array(3, 1, 4, 1, 1, 1), // 60
            array(2, 2, 1, 4, 1, 1), // 61
            array(4, 3, 1, 1, 1, 1), // 62
            array(1, 1, 1, 2, 2, 4), // 63
            array(1, 1, 1, 4, 2, 2), // 64
            array(1, 2, 1, 1, 2, 4), // 65
            array(1, 2, 1, 4, 2, 1), // 66
            array(1, 4, 1, 1, 2, 2), // 67
            array(1, 4, 1, 2, 2, 1), // 68
            array(1, 1, 2, 2, 1, 4), // 69
            array(1, 1, 2, 4, 1, 2), // 70
            array(1, 2, 2, 1, 1, 4), // 71
            array(1, 2, 2, 4, 1, 1), // 72
            array(1, 4, 2, 1, 1, 2), // 73
            array(1, 4, 2, 2, 1, 1), // 74
            array(2, 4, 1, 2, 1, 1), // 75
            array(2, 2, 1, 1, 1, 4), // 76
            array(4, 1, 3, 1, 1, 1), // 77
            array(2, 4, 1, 1, 1, 2), // 78
            array(1, 3, 4, 1, 1, 1), // 79
            array(1, 1, 1, 2, 4, 2), // 80
            array(1, 2, 1, 1, 4, 2), // 81
            array(1, 2, 1, 2, 4, 1), // 82
            array(1, 1, 4, 2, 1, 2), // 83
            array(1, 2, 4, 1, 1, 2), // 84
            array(1, 2, 4, 2, 1, 1), // 85
            array(4, 1, 1, 2, 1, 2), // 86
            array(4, 2, 1, 1, 1, 2), // 87
            array(4, 2, 1, 2, 1, 1), // 88
            array(2, 1, 2, 1, 4, 1), // 89
            array(2, 1, 4, 1, 2, 1), // 90
            array(4, 1, 2, 1, 2, 1), // 91
            array(1, 1, 1, 1, 4, 3), // 92
            array(1, 1, 1, 3, 4, 1), // 93
            array(1, 3, 1, 1, 4, 1), // 94
            array(1, 1, 4, 1, 1, 3), // 95
            array(1, 1, 4, 3, 1, 1), // 96
            array(4, 1, 1, 1, 1, 3), // 97
            array(4, 1, 1, 3, 1, 1), // 98
            array(1, 1, 3, 1, 4, 1), // 99
            array(1, 1, 4, 1, 3, 1), // 100
            array(3, 1, 1, 1, 4, 1), // 101
            array(4, 1, 1, 1, 3, 1), // 102
            Code128::START_A_ENCODING,
            Code128::START_B_ENCODING,
            Code128::START_C_ENCODING
        );

        /**
         * Attempts to retrieve the character set for the provided mode.
         *
         * @param the mode whose character set to retrieve
         *
         * @return the character set for the provided mode
         *
         * @throws EncoderException if the provided mode is not one of 
         * {@link Code128::MODE_A}, {@link Code128::MODE_B}, or 
         * {@link Code128::MODE_C}
         *
         */
        private function getCharsetFor($mode)
        {
            switch ($mode)
            {
                case Code128::MODE_A:
                    return Code128::CHARSET_A;
                case Code128::MODE_B:
                    return Code128::CHARSET_B;
                case Code128::MODE_C:
                    return Code128::CHARSET_C;
                default:
                    throw new EncoderException();
            }
        }

        /**
         * Attempts to retrieve the start encoding for the provided mode.
         *
         * @param mode the mode whose start encoding to retrieve
         *
         * @return the start encoding for the provided mode
         *
         * @throws EncoderException if the provided mode is not one of 
         * {@link Code128::MODE_A}, {@link Code128::MODE_B}, or 
         * {@link Code128::MODE_C}
         *
         */
        private function getStartSequenceFor($mode)
        {
            switch($mode)
            {
                case Code128::MODE_A:
                    return Code128::ENCODING_TABLE[Code128::START_A_INDEX];
                case Code128::MODE_B:
                    return Code128::ENCODING_TABLE[Code128::START_B_INDEX];
                case Code128::MODE_C:
                    return Code128::ENCODING_TABLE[Code128::START_C_INDEX];
                default:
                    throw new EncoderException();
            }
        }

        /**
         * Determines whether a particular mode (either A or B) is required 
         * or if the preferred mode (the mode that is currently being used) 
         * can remain active.
         *
         * @param text
         * @param offset
         * @param preferred
         */
        private function getMode00($text, $offset, $preferred)
        {
            $c = $text[$offset];
            if(ctype_lower($c) ||
            $c == '`' || $c == '{' || $c == '|' || $c == '}' || $c == '~' || $c == Code128::DEL)
            {
                return Code128::MODE_B;
            }
            else if(!ctype_alpha($c))
            {
                return Code128::MODE_A;
            }
            else if($preferred != Code128::MODE_C)
            {
                return $preferred;
            }
            return Code128::MODE_A;
        }
        
        private function getMode0($text, $offset, $preferredMode)
        {
            $c = $text[$offset++];
            if(($c != Code128::FNC1 && !(ctype_digit($c) &&
            ($offset < strlen($text) && ctype_digit($text[$offset])))))
            {
                if(ctype_lower($c) ||
                $c == '`' || $c == '{' || $c == '|' || $c == '}' || $c == '~' || $c == Code128::DEL)
                {
                    return Code128::MODE_B;
                }
                else if(!ctype_alpha($c))
                {
                    return Code128::MODE_A;
                }
                else if($offset < strlen($text))
                {
                    return $this->getMode00($text, $offset, $preferredMode);
                }
                else if($preferredMode != Code128::MODE_C)
                {
                    return $preferredMode;
                }
                return Code128::MODE_A;
            }
            else
            {
                return Code128::MODE_C;
            }
        }
        
        
        private function getMode($text, $offset)
        {
            $c = $text[$offset++];
            if(($c != Code128::FNC1 && !(ctype_digit($c) &&
            ($offset < strlen($text) && ctype_digit($text[$offset])))))
            {
                if(ctype_lower($c) ||
                $c == '`' || $c == '{' || $c == '|' || $c == '}' || $c == '~' || $c == Code128::DEL)
                {
                    return Code128::MODE_B;
                }
                else if(!ctype_alpha($c))
                {
                    return Code128::MODE_A;
                }
                else if($offset < strlen($text))
                {
                    return $this->getMode0($text, $offset, Code128::MODE_A);
                }
                return Code128::MODE_A;
            }
            else
            {
                return Code128::MODE_C;
            }
        }
        
        private function getModeStart($mode)
        {
            switch($mode)
            {
                case Code128::MODE_A:
                    return Code128::START_A;
                case Code128::MODE_B:
                    return Code128::START_B;   
                case Code128::MODE_C:
                    return Code128::START_C;
                default:
                    throw new EncoderException();
            }
        }
        
        private function getModeShift($mode)
        {
            switch($mode)
            {
                case Code128::MODE_A:
                    return Code128::SHIFT_A;
                case Code128::MODE_B:
                    return Code128::SHIFT_B;   
                case Code128::MODE_C:
                    return Code128::SHIFT_C;
                default:
                    throw new EncoderException();
            }
        }

        private function optimize($text)
        {
            $mode = $this->getMode($text, 0); // A, B, or C...0, 1, 2
            $rv = chr($this->getModeStart($mode));
            for ($i = 0; $i < strlen($text); $i++)
            {
                $c = $text[$i];
                $shift = $this->getMode($text, $i, $mode);
                if ($mode != $shift)
                {
                    $rv .= chr($this->getModeShift($shift));
                    $mode = $shift;
                }
                if($mode == Code128::MODE_C)
                {
                    if($c == Code128::FNC1)
                    {
                        $rv .= chr(Code128::FNC1);
                    }
                    else
                    {
                        $i++;
                        $rv .= $c .= $text[$i];
                    }
                }
                else
                {
                    $rv .= $c;
                }
            }
            return $rv;
        }

        private function modeForStartMode($char)
        {
            switch($char)
            {
                case Code128::START_A:
                    return Code128::MODE_A;
                case Code128::START_B:
                    return Code128::MODE_B;
                case Code128::START_C:
                    return Code128::MODE_C;
                default:
                    throw new EncoderException();
            }
        }
        
        private function startModeIndexForChar($char)
        {
            switch($char)
            {
                case Code128::START_A:
                    return Code128::START_A_INDEX;
                case Code128::START_B:
                    return Code128::START_B_INDEX;
                case Code128::START_C:
                    return Code128::START_C_INDEX;
                default:
                    throw new EncoderException();
            }
        }
        
        private function modeForShiftMode($char, $fallback)
        {
            switch($char)
            {
                case Code128::SHIFT_A:
                    return Code128::MODE_A;
                case Code128::SHIFT_B:
                    return Code128::MODE_B;
                case Code128::SHIFT_C:
                    return Code128::MODE_C;
                default:
                    return $fallback;
            }
        }
        
        private function getCharsetForMode($mode)
        {
            switch($mode)
            {
                case Code128::MODE_A:
                    return Code128::CHARSET_A;
                case Code128::MODE_B:
                    return Code128::CHARSET_B;
                case Code128::MODE_C:
                    return Code128::CHARSET_C;
                default:
                    throw new EncoderException();
            }
        }
        
        private function calculateChecksumIndex($text)
        {
            $optimizedEncoding = $this->optimize($text);
            $mode = $this->modeForStartMode(ord($optimizedEncoding[0]));
            $checksum = $this->startModeIndexForChar(ord($optimizedEncoding[0]));
            for($i = 1; $i < strlen($optimizedEncoding); $i++)
            {
                $c = $optimizedEncoding[$i];
                $shift = $this->modeForShiftMode($c, $mode);
                if($mode == Code128::MODE_C)
                {
                    if($c == Code128::FNC1)
                    {
                        $checksum += ($i * 102);
                    }
                    else if($c == Code128::SHIFT_B)
                    {
                        $checksum += ($i * 100);
                    }
                    else if($c == Code128::SHIFT_A)
                    {
                        $checksum += ($i * 101);
                    }
                    else
                    {
                        $checksum += ($i * (integer)($c . (string)$optimizedEncoding[$i + 1]));
                        $i++;
                    }
                }
                else
                {
                    $checksum += ($i * strpos($this->getCharsetFor($mode), $c));
                }
                if($shift != $mode)
                {
                    $mode = $shift;
                }
            }
            return $checksum % 103;
        }
        
        public final function encode($text)
        {
            $optimizedEncoding = $this->optimize($text);
            $currentMode = $this->modeForStartMode(ord($optimizedEncoding[0]));
            $optimizedEncoding = substr($optimizedEncoding, 1);
            
            $rv = array_merge(Code128::QUIET_ZONE, $this->getStartSequenceFor($currentMode));
            for($i = 0; $i < strlen($optimizedEncoding); $i++)
            {
                $c = $optimizedEncoding[$i];
                if($currentMode == Code128::MODE_C)
                {
                    if($c == Code128::FNC1)
                    {
                        $rv = array_merge($rv, Code128::ENCODING_TABLE[102]);
                    }
                    else if($c == Code128::SHIFT_A)
                    {
                        $rv = array_merge($rv, Code128::ENCODING_TABLE[101]);
                        $currentMode = Code128::MODE_A;
                    }
                    else if($c == Code128::SHIFT_B)
                    {
                        $rv = array_merge($rv, Code128::ENCODING_TABLE[100]);
                        $currentMode = Code128::MODE_B;
                    }
                    else
                    {
                        $i++;
                        $rv = array_merge($rv, Code128::ENCODING_TABLE[(integer)($c . $optimizedEncoding[$i])]);
                    }
                }
                else
                {
                    $index = strpos($this->getCharsetFor($currentMode), $c);
                    if ($index != -1)
                    {
                        $rv = array_merge($rv, Code128::ENCODING_TABLE[$index]);
                        $currentMode = $this->modeForShiftMode($c, $currentMode);
                    }
                    else
                    {
                        throw new EncoderException();
                    }
                }
            }
            return array_merge($rv, Code128::ENCODING_TABLE[$this->calculateChecksumIndex($text)], Code128::STOP_ENCODING, Code128::TERMINATION_ENCODING, Code128::QUIET_ZONE);
        }
        
        public final function decode($raw)
        {
        }
    }

    register_encoder('Code-128', new Code128());

