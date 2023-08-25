<?php

/*
 *
 *       _____      _     _      __  __  _____
 *      |  __ \    (_)   | |    |  \/  |/ ____|
 *      | |__) | __ _  __| | ___| \  / | |
 *      |  ___/ '__| |/ _` |/ _ \ |\/| | |
 *      | |   | |  | | (_| |  __/ |  | | |____
 *      |_|   |_|  |_|\__,_|\___|_|  |_|\_____|
 *            A minecraft bedrock server.
 *
 *      This project and it’s contents within
 *     are copyrighted and trademarked property
 *   of PrideMC Network. No part of this project or
 *    artwork may be reproduced by any means or in
 *   any form whatsoever without written permission.
 *
 *  Copyright © PrideMC Network - All Rights Reserved
 *
 *  www.mcpride.tk                 github.com/PrideMC
 *  twitter.com/PrideMC         youtube.com/c/PrideMC
 *  discord.gg/PrideMC           facebook.com/PrideMC
 *               bit.ly/JoinInPrideMC
 *  #StandWithUkraine                     #PrideMonth
 *
 */

declare(strict_types=1);

/**
 * Ban System API for Apache2.4
 *  - For WebAPI Minecraft Online Services (c) 2023
 *    Not afflinated by Mojang/Minecraft
 * All rights reserved. PrideStudio (c) 2019
 */

/** @var $package array **/
/**
 * Package Project Manager
 *   project.json contains several important files for the project.
 *   make sure you have this file or this software would corrupted.
 */
$package = json_decode(@file_get_contents("project.json"), true); // project.json
/** Always use content-type to json for web-api sites */

/** Always check .token file for api-key authorization key access. For fresh-install. */
if(!file_exists($package['token_file']) || @file_get_contents($package['token_file']) === ""){
	$newToken = base64_encode(zlib_encode($package['name'] . "+" . str_shuffle("1234567890") . "+v" . $package['version'], ZLIB_ENCODING_DEFLATE, 9));
	@file_put_contents(".token", base64_encode($newToken));
	header("Location: ?key=" . $newToken);
	return;
}

/** Always check authorization key if given. **/
if(!isset($_GET['key'])){
	header("Content-type: application/json");
	$json = ["error" => "Please specify the authorization key. If your authorization key has been lost. Please delete .token from the server directly to have renew one."];
	print(json_encode($json));
	return;
} else {
	if(!($_GET['key'] === base64_decode(@file_get_contents(".token"), true)) || ($_GET['key'] === "")){
		header("Content-type: application/json");
		$json = ["error" => "Invalid authorization key provided."];
		print(json_encode($json));
		return;
	}
}

/**
 * Sets a new token
 *  NOTE: You need your old token to renew a new token. Once its regenerated, the old token will be expired
 *        and the new token will be used as present token to the WebAPI.
 *  Usage: ?renewToken or ?newToken
 *         &key={authorization_key}
 */
if(isset($_GET["renewToken"]) || isset($_GET["newToken"])){
	header("Content-type: application/json");
	$newToken = base64_encode(zlib_encode("PrideWebAPI+" . str_shuffle("1234567890") . "+v" . $package['version'], ZLIB_ENCODING_DEFLATE, 9));
	@file_put_contents(".token", base64_encode($newToken));
	$json = ["message" => ["content" => "Renewed Api-Token! Please use this token as your key for all api authorization key.", "token" => $newToken]];
	print(json_encode($json));
	return;
}

/**
 * Gets the ban information stored in locally at the database.
 * Usage: ?getBan or ?banInfo
 *        &key={authorization_key}
 *        &username={player_name}
 * Method: GET
 * Type: SERVER POST
 * @return JsonResult
 */
if(isset($_GET["getBan"]) || isset($_GET["banInfo"])){
	header("Content-type: application/json");
	if(!isset($_GET["username"])){
		$json = ["error" => "Please specify username to be banned."];
		print(json_encode($json));
		return;
	}

	if(!file_exists("players/" . $_GET['username'] . ".json")){
		$json = ["error" => $_GET['username'] . " is not currently banned."];
		print(json_encode($json));
		return;
	} else {
		print(@file_get_contents("players/" . $_GET['username'] . ".json"));
		return;
	}
}

/**
 * Ban the specified player, locally add player to the database.
 * Usage: ?setBan or ?banUser
 *        &key={authorization_key}
 *        &username={player_name}
 *        &uuid={player_uuid}
 *        &reason={string}
 *        &until={base64_encode() DateTime->format() or Forever}
 *        &by={staff_name}
 * Method: GET
 * Type: SERVER POST
 * @return JsonResult
 */
if(isset($_GET["setBan"]) || isset($_GET['banUser'])){
	header("Content-type: application/json");
	if(!isset($_GET["username"])){
		$json = ["error" => "Please specify username to be banned."];
		print(json_encode($json));
		return;
	}

	if(!isset($_GET["until"])){
		$json = ["error" => "Please specify duration of ban by base64_encode() datetime the class."];
		print(json_encode($json));
		return;
	}

	if(!isset($_GET["by"])){
		$json = ["error" => "Please specify username of the staff."];
		print(json_encode($json));
		return;
	}

	if(file_exists("players/" . $_GET['username'] . ".json")){
		$json = ["error" => "This user is already banned. Try to use getBan?"];
		print(json_encode($json));
		return;
	}

	if(!isset($_GET["reason"])){
		$json = ["error" => "Please specify reason of the ban."];
		print(json_encode($json));
		return;
	}
	/** the body of json */
	$json[$_GET['username']] = [];
	$json[$_GET['username']]['reason'] = $_GET['reason'];
	if(!strtolower($_GET['until']) === "forever"){
		$json[$_GET['username']]['until'] = $_GET['until'];
	} else {
		$json[$_GET['username']]['until'] = null;
	}
	$json[$_GET['username']]['by'] = $_GET['by'];
	@file_put_contents("players/" . $_GET['username'] . ".json", json_encode($json));
	print(json_encode($json));
	return;
}

/**
 * Remove ban from the database.
 * Usage: ?removeBan or ?pardon or ?unban
 *        &key={authorization_key}
 *        &username={player_name}
 * Method: GET
 * Type: SERVER POST
 * @return JsonResult
 */
if(isset($_GET["pardon"]) || isset($_GET['removeBan']) || isset($_GET['unban'])){
	header("Content-type: application/json");
	if(!isset($_GET["username"])){
		$json = ["error" => "Please specify username to be banned."];
		print(json_encode($json));
		return;
	}

	if(!file_exists("players/" . $_GET['username'] . ".json")){
		$json = ["error" => "The player is not currently banned."];
		print(json_encode($json));
		return;
	} else {
		@unlink("players/" . $_GET['username'] . ".json");
		$json = ["message" => "Unbanned successfully. They can now join to the network."];
		print(json_encode($json));
		return;
	}
}

?>

<!-- fall back if auth key is provided but api is not provided -->
<html>
    <style>
        html {
            background-color: #424242;
            color: #fff;
            font-family: Arial;
        }
        
        code {
            background-color: #222222;
            padding: 2px;
            border-radius: 5px;
        }
        p {
            font-size: 20px;
        }
    </style>
    <title><?php echo $package['name'] . " v" . $package['version']; ?></title>
    <center><h1><?php echo $package['name'] . " v" . $package['version']; ?></h1><h2>~~~ You have an authorization key! ~~~</h2></center>
    <h2>What is this?</h2>
    <hr>
    <p style="color: #00FF11;">If the page was redirected you from the main WebAPI. That means you have a fresh install WebAPI server to your environment.</p>
    <p>Well, you have the key of this software. You can use as WebAPI Authorization Key. Keep it secret do not share to anyone! Use it wisely and handle with care. It is important for accessing the WebAPI.</p>
    <h2>General Information</h2>
    <hr>
    <p><b>Your authorization key: </b><code><?php echo base64_decode(@file_get_contents($package['token_file']), true); ?></code></p>
    
    <p><code>Usage: ?setBan or ?banUser</code> - <b>Ban the specified player, locally add player to the database.</b></p>
    <p><code>Usage: ?getBan or ?banInfo</code> - <b>Gets the ban information stored in locally at the database.</b></p>
    <p><code>Usage: ?renewToken or ?newToken</code> - <b>Sets a new token. <br><br> NOTE: You need your old token to renew a new token. Once its regenerated, the old token will be expired and the new token will be used as present token to the WebAPI.</b></p>
    <p><code>Usage: ?removeBan or ?pardon or ?unban</code> - <b>Remove ban from the database.</p>
    <hr>
    
    <p>That's all! Made by PrideStudio &copy; 2023. All rights reserved.</p>
</html>