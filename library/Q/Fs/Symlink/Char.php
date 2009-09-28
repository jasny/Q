<?php
namespace Q;

require_once 'Q/Fs/Char.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a char device.
 * 
 * @package Fs
 */
class Fs_Symlink_Char extends Fs_Char implements Fs_Symlink
{}
