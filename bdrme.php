<?php
/**
 * @package Business_Directory_RM_Endpoint
 * @version 0.1
 */
/*
Plugin Name: Business Directory Registration Magic Endpoint
Plugin URI: https://wordpress.org/plugins/bdrme/
Description: Auto populates a new Connections business directory listing when someone fills out your RegistrationMagic powered form.
Author: Josh Tempesta
Version: 0.1
Author URI: https://tempesta.us/
Text Domain: bdrme
*/




//Wordpress as usual..

add_action('admin_menu', 'bdrme_menu');

add_action('wp_ajax_bdrme', 'bdrme');
add_action('wp_ajax_nopriv_bdrme', 'bdrme');

function bdrme() {
	txtlog('bdrme_post');
	
	try 
	{					
		txtlog("2nd POST RECEIVED");
		
		txtlog(print_r($_POST, true));
		
		// if($_POST["code"] != "898")
			// die("You did not say the magic word");
		
		$firstname = $_POST["First_Name"];
		$lastname = $_POST["Last_Name"];
				
		$company = trim($_POST["Company_Name"]);
		$slug = str_replace(" ", "-", $company);
		$address = $_POST["Company_Address"];
		$phone = $_POST["Phone_Number"];
		$email = $_POST["Email_Address"];
		$website = $_POST["Company_Website"];
		$category = $_POST["Industry_Category"];
		$logo = $_POST["Photo_Upload"];
		
		//Debug Test Data
		if(IsNullOrEmptyString($firstname)){
			return;
			//$firstname = "Debug";		
		}
		if(IsNullOrEmptyString($lastname)){
			return;
			//$lastname = "User";
		}				
		
		$user =  get_user_by( "email", $email);

		if($user)
			$userid = $user->id;
		
		CreateEntry($firstname, $lastname, $company, $slug, $address, $phone, $email, $website, $category, $userid, $logo);
	
	}
	catch(Exception $e){
		txtlog("EXCEPTION: ".$e->getMessage());
	}
	
	die();
}

function bdrme_menu(){
	add_menu_page( 'BDRME Setup Page', 'BDRME', 'manage_options', 'BDRME', 'BDRME_init' );
}
			

function CreateEntry($firstname, $lastname, $company, $slug, $address, $phone, $email, $website, $category, $userid, $logo) {
	//$e->user = $userid;
	$entry = new cnEntry();
	$entry->setStatus( 'pending' );
	$entry->setEntryType( 'organization' );
	$entry->setOrganization( $company );
		
	$entry->setContactFirstName($firstname);
	$entry->setContactLastName($lastname);
	
	if($userid > 0)
		$entry->setUser($userid);

	
	if(!IsNullOrEmptyString($address)){
		$addressSplit = explode(",",$address);
		$entry->addresses->add(
			new cnAddress(
				array(
					'type' => 'work',
					
					'line_1'  => $addressSplit[0],
					
					'city'    => $addressSplit[2],
					
					'state'   => $addressSplit[3],
					
					'zipcode' => $addressSplit[5],
				)
			)
		);
		
		// $entry->image = 
			// new cnEntry_Image(
				// array(
					// '' => '',
				// )
			// )
		// );
		
		$entry->emailAddresses->add(
			new cnEmail_Address(
				array(
					'type' => 'work',
					'address' => $email,
					'visibility' => 'public',
				)
			)
		);
	}
	
		
	$entry->phoneNumbers->add(
		new cnPhone(
			array(
				'type' => 'workphone',
				'number' => $phone,
				'visibility' => 'public',
			)
		)
	);
	
	$entry->links->add(
		new cnLink(
			array(
				'title'  => $website,
				'url'    => $website,
			)
		)
	);

	
	
	
	 // * @param cnLink $link {
				 // *     @type int    $id         The link as it was retrieved from the db.
				 // *     @type string $type       The link type.
				 // *     @type string $name       The link type name.
				 // *     @type string $visibility The link visibility.
				 // *     @type int    $order      The index order of the link.
				 // *     @type bool   $preferred  Whether the link is the preferred link or not.
				 // *     @type string $title      The link text title.
				 // *     @type string $url        The link URL.
				 // *     @type string $target     If the link should open in new tab/window or in the same tab/window.
				 // *                              VALID: new|same
				 // *     @type bool   $follow     Whether or not the link should be followed.
				 // *     @type bool   $image      Whether or not the link is attached to the image (photo).
				 // *     @type bool   $logo       Whether or not the link is attached to the logo.
				 // * }
				 
	// $entry->links->add(
		// new cnEntry_Collection_Item(
			// array(
				// 'type' => 'Website',
				// 'name' => $website,
				// 'url' => $website,
				// 'visibility' => 'public',
			// )
		// )
	// );

	$result = $entry->save();

	if ( FALSE !== $result ) {
		
		$category = trim($category);
		$category = str_replace('&', '&amp;', $category);
		
		$category_object = cnTerm::getBy( 'name', $category, 'category' );
		txtlog("category is: ".$category);
		if($category_object) 
		{			
			$category_id = $category_object->term_id;
			txtlog("category id for: ".$category." : ".$category_id);			
					
			//Connections_Directory()->term->setTermRelationships( $entry->getId(), $default, 'category' );
			if($category_id > 0)
				Connections_Directory()->term->setTermRelationships( $entry->getId(), array($category_id), 'category' );
			
			if($userid > 0)
				add_user_meta( $userid, 'connections_entry_id', $entry->getId());
			
			txtlog("Entry created and category assigned!");
		}
	}
	else 
		throw new Exception('Business directory entry did not save');
	
	
}		

function BDRME_init(){
	echo "Coming soon!";
	//echo "<h1>Business Directory Registration Magic Endpoint</h1><hr/><p>To use this plugin please copy and paste the following URL into your RegistrationMagic powered form.<br/> There is an option that allows the form to post to an URL. Copy and paste the following into that field. Your business directory entry should auto populate after someone fills it out.</p>";
	
	//$file_path = __DIR__;
	//$url_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
	
	//echo "<h2>".$url_path."</h2>";
}

// Function for basic field validation (present and neither empty nor only white space
function IsNullOrEmptyString($str){
	return (!isset($str) || trim($str) === '');
}

function txtlog($str) {
	
	$d = date("Y-m-d h:i:sa");
	$str = $d . " : " . $str;
	
	$file_path = __DIR__;
	$url_path = str_replace($_SERVER['DOCUMENT_ROOT'], '', $file_path);
	
	$rtr = file_put_contents($url_path.'\bdrme.txt', $str.PHP_EOL , FILE_APPEND | LOCK_EX);
	
	return $rtr;
}