<?php
/**
 * Created by PhpStorm.
 * User: udit
 * Date: 15/10/16
 * Time: 09:45
 */

// array - repo names and local filesystem path and branch
$repos  = array(
	'wayfarer-yelp-data' => array (
		'branch' => 'master',
		'localpath' => '/var/www/wayfarer.incognitech.in/htdocs',
	),
);

hook_write_log( 'Hello at ' . date( 'D M j G:i:s T Y' ) );
hook_write_log( 'Raw Input Dump (HTTP POST)' );
hook_write_log( file_get_contents( 'php://input' ) );

$payload = json_decode( file_get_contents( 'php://input' ) );

if ( ! $payload ) {
	hook_write_log( 'NULL paylod. EXIT ' );
	exit();
}

hook_write_log( 'Dump payload' );
hook_write_log( $payload );

hook_write_log( 'Dump Repos Array' );
hook_write_log( $repos );

hook_write_log( 'Dump macthing keys from array based on payload' );
hook_write_log( $repos[ $payload->repository->name ] );
hook_write_log( $repos[ $payload->repository->name ]['branch'] );
hook_write_log( $repos[ $payload->repository->name ]['localpath'] );

$remote_branch = end( explode( '/', $payload->ref ) );
hook_write_log( 'Extract remote branch' );
hook_write_log( $remote_branch );

if( ! isset( $repos[ $payload->repository->name ]['branch'] )
    || $repos[ $payload->repository->name ]['branch'] == $remote_branch ) {

	hook_write_log( 'Inside if-else block' );
	$command = 'cd ' . $repos[ $payload->repository->name ]['localpath'] . '/ && git reset --hard HEAD && git pull' ;
	hook_write_log( $command );
	$res = shell_exec( $command );
	hook_write_log( $res);
	hook_write_log( 'FINISHED ' . date( 'D M j G:i:s T Y' ) );
}

function hook_write_log( $str ) {

	if ( is_string( $str ) ) {
		file_put_contents( 'hook.log', "\n\n" . $str . "\n\n" , FILE_APPEND );
	} elseif ( is_array( $str ) ) {
		hook_write_log( var_export( $str, true ) );
	} elseif ( is_object( $str ) ) {
		hook_write_log( var_export( ( array ) $str, true ) ) ;
	} else {
		hook_write_log( 'ERROR: Paramter is not string or array. Instead it\'s ' . gettype( $str ) );
	}
}
?>