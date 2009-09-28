<?php
namespace Q;

require_once 'Q/Fs/Socket.php';
require_once 'Q/Fs/Symlink.php';

/**
 * Interface of a symlink to a socket.
 * 
 * @package Fs
 */
class Fs_Symlink_Socket extends Fs_Socket implements Fs_Symlink
{}
