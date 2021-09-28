<?php

namespace Slate\Exception {
    class IOException extends SlateException {
        public const ERROR_DIR_NOT_FOUND     = (1<<0);
        public const ERROR_FILE_NOT_FOUND    = (1<<1);
        public const ERROR_PATH_NOT_FOUND    = 
            IOException::ERROR_DIR_NOT_FOUND
            | IOException::ERROR_FILE_NOT_FOUND;
        
        public const ERROR_DIR_OPEN_FAILURE  = (1<<2);
        public const ERROR_FILE_OPEN_FAILURE = (1<<3);
        public const ERROR_PATH_OPEN_FAILURE = 
            IOException::ERROR_DIR_OPEN_FAILURE
            | IOException::ERROR_FILE_OPEN_FAILURE;

        public const ERROR_FILE_IS_DIR_MISMATCH = (1<<4);
        public const ERROR_DIR_IS_FILE_MISMATCH = (1<<5);

        public const ERROR_UNRESOLVABLE_PATH    = (1<<6);
        public const ERROR_UNSAFE_PATH          = (1<<7);

        public const ERROR_MESSAGES       = [
            IOException::ERROR_DEFAULT              => "Unknown IO error while accessing '{path}'.",
            IOException::ERROR_DIR_NOT_FOUND        => "Directory at '{path}' doesn't exist.",
            IOException::ERROR_FILE_NOT_FOUND       => "File at '{path}' doesn't exist.",
            IOException::ERROR_PATH_NOT_FOUND       => "Path '{path}' doesn't exist.",

            IOException::ERROR_DIR_OPEN_FAILURE     => "Unable to open directory at '{path}'.",
            IOException::ERROR_FILE_OPEN_FAILURE    => "Unable to open file at '{path}'.",
            IOException::ERROR_PATH_OPEN_FAILURE    => "Unable to path '{path}'.",

            IOException::ERROR_FILE_IS_DIR_MISMATCH => "Expecting file, got directory '{path}'.",
            IOException::ERROR_DIR_IS_FILE_MISMATCH => "Expecting directory, got file '{path}'.",

            IOException::ERROR_UNRESOLVABLE_PATH    => "Unable to resolve path '{path}'.",

            IOException::ERROR_UNSAFE_PATH          => "Sub path '{subPath}' is not a child of root path '{rootPath}'."
        ];
    }
}


?>