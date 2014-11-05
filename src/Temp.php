<?php

namespace sndsgd\util;

use \Exception;


/**
 * Temp file and directory utility methods
 */
class Temp
{
   /**
    * All created paths are added here for easy removal at script exit
    * 
    * @var array.<string => boolean|null>
    */
   private static $files = [];

   /**
    * Register a temp path to be deleted when the script exists
    * 
    * @param string $path An absolute path
    * @param boolean|null $isDir Whether or not $path is a directory
    */
   public static function registerPath($path, $isDir = null)
   {
      if (count(self::$files) === 0) {
         register_shutdown_function('sndsgd\\util\\Temp::cleanup');
      }
      self::$files[$path] = $isDir;
   }

   /**
    * Create a temp file
    * 
    * @param string $prefix A prefix for the file name
    * @param string|null $contents Optional contents for the file
    * @return string The path to the newly created temp file
    */
   public static function file($prefix = 'temp-', $contents = null) 
   {
      $path = tempnam(sys_get_temp_dir(), $prefix);
      if ($contents) {
         file_put_contents($path, $contents);
      }
      self::registerPath($path, false);
      return $path;
   }

   /**
    * Create a temp directory
    * 
    * @param string $prefix A prefix for the directory name
    * @return string The path to the newly created temp directory
    */
   public static function dir($prefix = 'temp')
   {
      $tmpdir = sys_get_temp_dir();
      do {
         $rand = substr(md5(microtime(true)), 0, 6);
         $path = $tmpdir.DIRECTORY_SEPARATOR.$prefix.$rand;
      }
      while (@mkdir($path) === false);

      self::registerPath($path, true);
      return $path;
   }

   /**
    * Remove all temp files & directories created since script start
    * 
    * @return boolean 
    */
   public static function cleanup()
   {
      $ret = true;
      foreach (self::$files as $path => $isDir) {
         if (file_exists($path)) {
            $isDir = ($isDir === null) ? is_dir($path) : $isDir;
            $result = ($isDir === true) ? Dir::remove($path) : @unlink($path);
            if ($result !== true) {
               $ret = false;
            }
         }
      }
      return $ret;
   }
}
