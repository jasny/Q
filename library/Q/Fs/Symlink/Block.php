<?php
namespace Q;

require_once 'Q/Fs/Block.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a block device file.
 * 
 * @package Fs
 */
class Fs_Symlink_Block extends Fs_Block implements Fs_Symlink
{}
