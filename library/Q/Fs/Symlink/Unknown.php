<?php
namespace Q;

require_once 'Q/Fs/Unknown.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to file with an unknown type.
 * 
 * @package Fs
 */
class Fs_Symlink_Unknown extends Fs_Unknown implements Fs_Symlink
{}
