<?php

namespace sndsgd\util;

use \Exception;
use \InvalidArgumentException;


/**
 * File utility methods
 */
class File
{
   const KILOBYTE = 1024;
   const MEGABYTE = 1048576;
   const GIGABYTE = 1.074e+9;
   const TERABYTE = 1.1e+12;

   // bitmask values for use with sndsgd\Path::test()
   // @see sndsgd\Path for sub values
   const READABLE = 19;
   const WRITABLE = 11;
   const READABLE_WRITABLE = 27;
   const EXECUTABLE = 35;

   /**
    * Verify if a file is readable
    * 
    * @param string $path An absolute path to the file to test
    * @return boolean:true The file is readable
    * @return string An error message indicating why the path test failed
    */
   public static function isReadable($path)
   {
      return Path::test($path, self::READABLE);
   }

   /**
    * Determine if a file path can be written to
    * 
    * NOTE: if the path does not exist, the parent directories will be analyzed
    * @param string $path An absolute path to the file to test
    * @return boolean:true The file is writable
    * @return string An error message indicating why the path test failed
    */
   public static function isWritable($path)
   {
      # if the file doesn't exist, ensure its parent dir is writable
      if (!file_exists($path)) {
         return Dir::isWritable(dirname($path));
      }
      return Path::test($path, self::WRITABLE);
   }

   /**
    * Prepare a file for writing
    * 
    * @param string $path An absolute path to the file to write
    * @param octal $dirPerms Permissions for new directories
    * @return boolean|string
    * @return boolean:true The path is ready for writing
    * @return string An error message indicating where the prepare failed
    */
   public static function prepare($path, $dirPerms = 0775)
   {
      if (file_exists($path)) {
         return Path::test($path, self::WRITABLE);
      }
      return Dir::prepare(dirname($path), $dirPerms);
   }

   /**
    * Separate a filename and extension
    * 
    * bug (??) with pathinfo(): 
    * [http://bugs.php.net/bug.php?id=67048](http://bugs.php.net/bug.php?id=67048)
    * 
    * Example Usage:
    * <code>
    * $path = '/path/to/file.txt';
    * list($name, $ext) = File::splitName($path);
    * // => ['file', 'txt']
    * $ext = File::splitName($path)[1];
    * // => 'txt'
    * </code>
    * 
    * @param string $path A file path or filename
    * @return array
    * - [0] string basename
    * - [1] string|null extension
    *
    */
   public static function splitName($path)
   {
      $pos = strrpos($path, '/');
      if ($pos !== false && $pos > 0) {
         $path = substr($path, $pos + 1);
      }

      $extpos = strrpos($path, '.');
      if ($extpos === false) {
         $name = substr($path, 0, $extpos);
         $ext = null;
      }
      else if ($extpos === 0) {
         $name = $path;
         $ext = null;
      }
      else {
         $name = substr($path, 0, $extpos);
         $ext = substr($path, $extpos + 1);
      }
      return [$name, $ext];
   }

   /**
    * Combine prepare, chmod, and rename into a single step
    * 
    * @param string $from An absolute path to the file before moving
    * @param string $to An absolute path to the file after moving
    * @param octal $fperm An octal to pass to chmod
    * @param octal $dperm New directory permissions
    * @return boolean|string All operations were successful
    * @return boolean:true All operations were successful
    * @return string Error message indicating which operation failed
    */
   public static function rename($from, $to, $fperm = 0664, $dperm = 0775)
   {
      $test = Path::test($from, self::READABLE_WRITABLE);
      if ($test !== true) {
         return $test;
      }
      $test = self::prepare($to, $dperm);
      if ($test !== true) {
         return $test;
      }
      else if (@rename($from, $to) === false) {
         return "failed to move '$from' to '$to'";
      }
      else if ($fperm !== null && @chmod($to, $fperm) === false) {
         return "failed to set permissions for '$from' to '$fperm'";
      }
      return true;
   }

   /**
    * Get a human readable file size
    * 
    * @param integer|string $bytes Bytes or an absolute file path
    * @param integer $precision The number of decimal places to return
    * @return string The formatted filesize
    * @throws InvalidArgumentException
    *   if $bytes is interpretted as a file path and does not exist
    */
   public static function formatSize($bytes, $precision = 2)
   {
      if (is_string($bytes)) {
         if (is_numeric($bytes)) {
            $bytes = (int) $bytes;
         }
         else if (is_file($bytes)) {
            $bytes = filesize($bytes);
         }
         else {
            throw new InvalidArgumentException(
               "invalid value provided for 'bytes'; ".
               "expecting an integer or an absolute file path as string"
            );
         }
      }
      else if (!is_int($bytes)) {
         throw new InvalidArgumentException(
            "invalid value provided for 'bytes'; ".
            "expecting an integer or an absolute file path as string"
         );
      }

      $i = 0;
      $sizes = ['bytes','KB','MB','GB','TB','PB','EB'];
      while ($bytes > 1024) {
         $bytes /= 1024;
         $i++;
      }
      return number_format($bytes, $precision).' '.$sizes[$i];
   }

   /**
    * Count the lines in a file without reading the contents into memory
    * 
    * @param string $path An absolute file path
    * @return integer The number of lines in the file
    * @throws Exception the file is not readable or a read error occurs
    */
   public static function countLines($path)
   {
      $ret = 0;
      if (!($fh = @fopen($path, 'r'))) {
         throw new Exception('failed to open file');
      }
      while (fgets($fh) !== false) {
         $ret++;
      }
      fclose($fh);
      return $ret;
   }
}

