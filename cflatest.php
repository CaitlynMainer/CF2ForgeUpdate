<?PHP
include_once 'hquery.php';
require_once 'cflatest_conf.php';
hQuery::$cache_path = "cache";

use duzun\hQuery; // Optional (PHP 5.3+)

function decodeHtmlEnt($str) {
    $ret = html_entity_decode($str, ENT_COMPAT, 'UTF-8');
    $p2 = -1;
    for(;;) {
        $p = strpos($ret, '&#', $p2+1);
        if ($p === FALSE)
            break;
        $p2 = strpos($ret, ';', $p);
        if ($p2 === FALSE)
            break;
           
        if (substr($ret, $p+2, 1) == 'x')
            $char = hexdec(substr($ret, $p+3, $p2-$p-3));
        else
            $char = intval(substr($ret, $p+2, $p2-$p-2));
           
        //echo "$char\n";
        $newchar = iconv(
            'UCS-4', 'UTF-8',
            chr(($char>>24)&0xFF).chr(($char>>16)&0xFF).chr(($char>>8)&0xFF).chr($char&0xFF)
        );
        //echo "$newchar<$p<$p2<<\n";
        $ret = substr_replace($ret, $newchar, $p, 1+$p2-$p);
        $p2 = $p + strlen($newchar);
    }
    return $ret;
}

$arrayLength = count($projects);
for ($i = 0; $i < $arrayLength; $i++) {
    $printed_vers = array();
    // GET the document
    $doc = hQuery::fromUrl($projects[$i]['url'].'/files', ['Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);

    $files = $doc->find('.project-file-list-item');
    $gversions = array();
    $promo_versions = array();
    if ( $files ) {
        // Iterate over the result
        foreach($files as $pos => $tag) {
            //Get the URL to the file so we can get the changelog
            $blep = $tag->find('.project-file-name > div > .project-file-name-container > a');
            //make the request for the file's page, and grab the changelog
            $doc2 = hQuery::fromUrl($blep->attr('href'), ['Accept' => 'text/html,application/xhtml+xml;q=0.9,*/*;q=0.8']);
            $change = $doc2->find('.details-changelog > .logbox');
            //parse the changelog to fit how ForgeUpdate wants it.
            $change = str_replace("<br>","\n",str_replace("\r\n","",$change));
            $change = preg_replace( '/\h+/', ' ', $change);
            $change = strip_tags($change);
            $change = decodeHtmlEnt($change);
            
            //get the version of the mod we've picked up.
            $modver = $tag->find('.project-file-name > div > .project-file-name-container');
            $modver = $modver->text();
            preg_match("/".$projects[$i]['regex']."/", $modver, $out);
            $modver = $out[1];
            //get the supported MC versions if '.additional-versions' exists we have to regex it out of the tool tip 
            $blop = $tag->find('.project-file-game-version > .additional-versions');
            if(isset($blop)) {
                preg_match_all("/(\d+.\d+.?\d+)/", $blop->attr('title'), $versions);
                $version = $versions[1];
            } else {
                //otherwise we can just get it from the raw version label
                 $blop = $tag->find('.project-file-game-version > .version-label');
                 $version = array($blop->text());
            }
            //Print the recomended versions or the "Promos"
            foreach($version as $ver) {
                if(!array_key_exists($ver, $printed_vers)) {
                    $printed_vers[$ver] = $ver;
                    $promo_versions[$ver."-recomended"] = $modver;
                }
            }
            //Print everything else grouped by MC version.
            foreach($version as $ver) {
                    $gversions[$ver][$modver] = $change;
            }
        }

        $output = array("homepage"=>$projects[$i]['url'],"promos"=>$promo_versions);
        $newout=array_merge($output,$gversions);
        $jsonout = json_encode($newout,JSON_PRETTY_PRINT);
        $fp = fopen($projects[$i]['cachefile'].'.json', 'w');
        fwrite($fp, $jsonout);
        fclose($fp);
        echo 'saved '.$projects[$i]['cachefile'].'.json<br>';
    }
}
echo "Done";