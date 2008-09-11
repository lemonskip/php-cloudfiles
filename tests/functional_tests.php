<?php
require("capon.php");
require("cloudfs_ini.php");  # account settings

function assert_callback($file, $line, $code)
{
    print "Assertion failed:\n";
    print "  File: " . $file . "\n";
    print "  Line: " . $line . "\n";
    print "  Code: " . $code . "\n";
    throw new Exception("error");
}

assert_options(ASSERT_ACTIVE, 1);
assert_options(ASSERT_WARNING, 0);
assert_options(ASSERT_QUIET_EVAL, 1);
assert_options(ASSERT_CALLBACK, "assert_callback");

$auth = new CLOUDFS_Authentication($USER,$PASS,$ACCOUNT,$HOST);
$auth->authenticate();
assert('$auth->getStorageUrl() != NULL');
assert('$auth->getStorageToken() != NULL');
$conn = new CLOUDFS_Connection($auth);


echo "======= LIST CONTAINERS =====================================\n";
$orig_containers = $conn->list_containers();
assert('is_array($orig_containers)');
print_r($orig_containers);


echo "======= CREATE CONTAINER ====================================\n";
$container = $conn->create_container("php-capon");
assert('$container');
print $container."\n";


echo "======= CREATE CONTAINER (WITH '/' IN NAME) =================\n";
try {
    $bad_cont = $conn->create_container("php/capon");
} catch (SyntaxException $e) {
    print "SUCCESS: do not allow '/' in container name\n";
}

echo "======= CREATE OBJECT (WITH '/' IN NAME) ====================\n";
$o0 = $container->create_object("slash/name");
assert('$o0');
print $o0 . "\n";


echo "======= UPLOAD STRING CONTENT FOR OBJECT(0) =================\n";
$text = "Some sample text.";
$md5 = md5($text);
$o0->content_type = "text/plain";
$result = $o0->write($text);
assert('$result');
assert('$o0->getETag() == $md5');
print $o0."\n";


echo "======= CREATE OBJECT (WITH ' ' IN NAME) ====================\n";
$ospace = $container->create_object("space name");
assert('$ospace');
print $ospace . "\n";


echo "======= UPLOAD STRING CONTENT FOR OBJECT(SPACE) =============\n";
$text = "Some sample text.";
$md5 = md5($text);
$ospace->content_type = "text/plain";
$result = $ospace->write($text);
assert('$result');
assert('$ospace->getETag() == $md5');
print $ospace."\n";


echo "======= CREATE OBJECT =======================================\n";
$o1 = $container->create_object("fuzzy.txt");
assert('$o1');
print $o1 . "\n";


echo "======= UPLOAD STRING CONTENT FOR OBJECT(1) =================\n";
$text = "This is some sample text.";
$md5 = md5($text);
$o1->content_type = "text/plain";
$result = $o1->write($text);
assert('$result');
assert('$o1->getETag() == $md5');
print $o1."\n";


echo "======= UPLOAD STRING CONTENT FOR OBJECT(2) =================\n";
$o1->content_type = "text/plain";
$result = $o1->write("Even more sample text.");
assert('$result');
print $o1."\n";


echo "======= RE-UPLOAD STRING CONTENT FOR OBJECT WITH METADATA ===\n";
$text = "This is some different sample text.";
$md5 = md5($text);
$o1->content_type = "text/plain";
$o1->metadata = array(
    "Foo" => "This is foo",
    "Bar" => "This is bar");
$result = $o1->write($text);
assert('$result');
assert('$o1->getETag() == $md5');
print $o1."\n";


echo "======= UPLOAD OBJECT FROM FILE =============================\n";
$fname = basename(__FILE__);
$md5 = md5_file($fname);
$o2 = $container->create_object($fname);
$result = $o2->load_from_filename($fname);
assert('$result');
assert('$o2->getETag() == $md5');
print $o2."\n";


echo "======= GET CONTAINER =======================================\n";
$cont2 = $conn->get_container("php-capon");
assert('$cont2');
print $cont2 . "\n";


echo "======= GET OBJECT ==========================================\n";
$o3 = $container->get_object("fuzzy.txt");
assert('$o3->getETag() == $o1->getETag()');
print $o3."\n";
print "  etag: " . $o3->getETag() . "\n";
print "  content-type: " . $o3->content_type . "\n";
print "  content-length: " . $o3->content_length . "\n";
print_r($o3->metadata);


echo "======= UPDATE OBJECT METADATA ==============================\n";
$o3->metadata = array(
    "NewFoo" => "This is new foo",
    "NewBar" => "This is new bar");
$result = $o3->sync_metadata();
assert('$result');
print $o3."\n";


echo "======= VERIFY UPDATED METADATA =============================\n";
$o4 = $container->get_object("fuzzy.txt");
assert('$o4->getETag() == $o3->getETag()');
print "SUCCESS\n";
print_r($o3->metadata);
print_r($o4->metadata);


echo "======= CREATE OBJECT =======================================\n";
$o5 = $container->create_object("fubar.txt");
assert('$o5');
print $o5."\n";


echo "======= UPLOAD STRING CONTENT FOR OBJECT(1) =================\n";
$text = "This is more sample text for a different object.";
$md5 = md5($text);
$o5->content_type = "text/plain";
$result = $o5->write($text);
assert('$result');
assert('$o5->getETag() == $md5');
print $o5."\n";


echo "======= DOWNLOAD OBJECT TO FILENAME =========================\n";
$result = $o4->save_to_filename("/tmp/fuzzy.txt");
assert('$result');
print "WROTE DATA TO /tmp/fuzzy.txt, cat /tmp/fuzzy.txt\n";
passthru("cat /tmp/fuzzy.txt");
print "\n";


echo "======= DOWNLOAD OBJECT TO STRING ===========================\n";
$data = $o4->read();
assert('$data');
print $data . "\n";


echo "======= LIST OBJECTS (ALL) ==================================\n";
$obj_list = $container->list_objects();
assert('is_array($obj_list) && !empty($obj_list)');
print_r($obj_list);


echo "======= CHECK ACCOUNT INFO ==================================\n";
list($num_containers, $total_bytes) = $conn->get_info();
assert('$num_containers >= 1');
assert('$total_bytes >= 7478');
print "num_containers: " . $num_containers . "\n";
print "   total bytes: " . $total_bytes . "\n";


echo "======= FIND OBJECTS (LIMIT) ================================\n";
$obj_list = $container->list_objects(1);
assert('is_array($obj_list) && !empty($obj_list)');
print_r($obj_list);


echo "======= FIND OBJECTS (LIMIT,OFFSET) =========================\n";
$obj_list = $container->list_objects(1,1);
assert('is_array($obj_list) && !empty($obj_list)');
print_r($obj_list);


echo "======= FIND OBJECTS (PREFIX='fu') ==========================\n";
$obj_list = $container->list_objects(0,-1,"fu");
assert('is_array($obj_list) && !empty($obj_list)');
print_r($obj_list);


echo "======= DELETE CONTAINER (FAIL) =============================\n";
try {
    $conn->delete_container($container);
} catch (NonEmptyContainerException $e) {
    print "SUCCESS: " . $e->getMessage() . "\n";
}


echo "======= DELETE OBJECTS ======================================\n";
$obj_list = $container->list_objects();
print_r($obj_list);
foreach ($obj_list as $obj) {
    $result = $container->delete_object($obj);
    assert('$result');
}

echo "======= LIST OBJECTS ========================================\n";
$obj_list = $container->list_objects();
assert('empty($obj_list)');
print "SUCCESS: empty container\n";


echo "======= DELETE CONTAINER (PASS) =============================\n";
$result = $conn->delete_container($container);
assert('$result');
print "SUCCESS: container deleted\n";

echo "======= LIST CONTAINERS =====================================\n";
$containers = $conn->list_containers();
assert('$containers == $orig_containers');
print_r($orig_containers);
print_r($containers);


/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * c-hanging-comment-ender-p: nil
 * End:
 */
?>
