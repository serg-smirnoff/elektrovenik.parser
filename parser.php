<?php

include_once ("urls.inc.php");

function getPageContent($url, $agent = false)
{
   $contentPage = '';
   $ch = curl_init();
   curl_setopt($ch, CURLOPT_HEADER, 0);
   curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
   curl_setopt($ch, CURLOPT_TIMEOUT, 30);
   curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)");
   curl_setopt($ch, CURLOPT_URL, $url);
   $contentPage = curl_exec($ch);
   curl_close($ch);
   return trim($contentPage);
}

function parse($content, $url, $path, $filename, $parent)
{
	
	/* $CSV_PARENT - id родителя, куда заливать */
	$CSV_PARENT = $parent;
	
	/* $CSV_PRICE - цена */
	
	$start_str = '<span class="value">';
	$stop_str = '</span>';	 

	$rule = "!".$start_str."(.*?)".$stop_str."!si";	
    preg_match($rule,$content,$price);
	
	$price[1] = str_replace(" руб.","",$price[1]);
	$price[1] = str_replace("`","",$price[1]);
	
	$CSV_PRICE = $price[1];
	
	/* $CSV_CONTENT1 - краткое описание */

	$start_str = '<ul class="tec-char">';
	$stop_str = '</ul>';	 

	$rule = "!".$start_str."(.*?)".$stop_str."!si";	
	preg_match($rule,$content,$content1);

	$CSV_CONTENT1 = $content1[0]; // save to csv
	
		/* $CSV_BRAND - brand */
		
		$start_str = 'href="http://www.electrovenik.ru/catalog/small/';
		$stop_str = '/a>';

		$rule = "!".$start_str."(.*?)".$stop_str."!si";	
		preg_match($rule,$content1[1],$brand);

		$start_str = '">';
		$stop_str = '<';

		$rule = "!".$start_str."(.*?)".$stop_str."!si";	
		preg_match($rule,$brand[1],$brand);

		$CSV_BRAND = $brand[1];
		
	/* $CSV_IMAGE_SMALL - малое изображение */

	$start_str_image = '<img src="/gallery/';
	$stop_str_image = '"';	 

	$rule_image = "!".$start_str_image."(.*?)".$stop_str_image."!si";	
	preg_match($rule_image,$content,$image_small);
		
	$image_small_name = $image_small[1];

	$CSV_IMAGE_SMALL = $path.$image_small_name; // save to csv + path
	
		/* small_image_save_to_folder - сохранить изображение позиции на сервер */	

		$path_to_image_small = "http://www.electrovenik.ru/gallery/".$image_small_name;
		$image = file_get_contents($path_to_image_small);
		file_put_contents("/var/www/serg-smirnoff/data/www/chicgirls.ru/parser/".$path.$image_small_name, $image);

		
	/* $CSV_PAGETITLE - заголовок позиции */

	$start_str = '<p class="cat-header">';
	$stop_str = '</p>';	
	
	$rule = "!".$start_str."(.*?)".$stop_str."!si";	
	preg_match($rule,$content,$pagetitle);

	$start_str = '>';
	$stop_str = '<';	
	
	$rule = "!".$start_str."(.*?)".$stop_str."!si";	
	preg_match($rule,$pagetitle[1],$title);
	
	$CSV_PAGETITLE = $title[1];

	/* sub_url - ссылка на страницу подробнее */
		
		$start_str2 = '<a href="';
		$stop_str2 = '">';
		$rule2 = "!".$start_str2."(.*?)".$stop_str2."!si";
		
		preg_match($rule2,$pagetitle[1],$sub_url);
		
		$content_sub = getPageContent($sub_url[1]);

		$start_str = '<img src="/gallery/';
		$stop_str = '"';	
		$rule = "!".$start_str."(.*?)".$stop_str."!si";	
		
		preg_match($rule, $content_sub, $image_big);
		$image_big_name = $image_big[1];

		$CSV_IMAGE_BIG = $path.$image_big_name; 
		
			/* small_image_save_to_folder - сохранить изображение позиции на сервер */	

			$path_to_image_big = "http://www.electrovenik.ru/gallery/".$image_big_name;
			$image2 = file_get_contents($path_to_image_big);
			file_put_contents("/var/www/serg-smirnoff/data/www/chicgirls.ru/parser/".$path.$image_big_name, $image2);
		
		/* content2 - технические характеристики позиции */
		
		$start_str = '<table class="item-tec-char" id="tthtable">';
		$stop_str = '</table>';
		$rule = "!".$start_str."(.*?)".$stop_str."!si";
		
		preg_match($rule, $content_sub, $content2);
		$CSV_CONTENT2 = $content2[0];
/*				
		echo "pagetitle= ".$CSV_PAGETITLE."<br />";
		echo "price= ".$CSV_PRICE."<br />";
		echo "content1= ".$CSV_CONTENT1."<br />";
		echo "content2= ".$CSV_CONTENT2."<br />";
		echo "image_small= ".$CSV_IMAGE_SMALL."<br />";
		echo "image_big= ".$CSV_IMAGE_BIG."<br />";
		echo "brand= ".$CSV_BRAND."<br />";
*/
		/* сохраняем в csv */

		$fp = fopen ("/var/www/serg-smirnoff/data/www/chicgirls.ru/parser/export/".$filename, "a+");	
		fwrite ($fp, $CSV_PARENT);fwrite($fp,";");fwrite($fp,$CSV_PAGETITLE);fwrite($fp,";");fwrite($fp,trim(str_replace("\n", "", str_replace("\r", "", $CSV_CONTENT1))));fwrite($fp,";");fwrite($fp,trim(str_replace(array("\n","\r","&nbsp;",";"),array("","","",""),$CSV_CONTENT2)));fwrite($fp,";");fwrite($fp,$CSV_PRICE);fwrite($fp,";");fwrite($fp,$CSV_IMAGE_SMALL);fwrite($fp,";");fwrite($fp,$CSV_IMAGE_BIG);fwrite($fp,";");fwrite($fp,$CSV_BRAND);fwrite($fp,";");
		fwrite ($fp,"\n");
		fclose ($fp);
		
}


foreach ($urls as $key => $arr){

	$url = $arr["url"];
	$path = $arr["path"];
	$filename = $arr["filename"];

	if ($key == 01){
		$fp = fopen ("/var/www/serg-smirnoff/data/www/chicgirls.ru/parser/export/".$filename, "a+");	
		fwrite ($fp, "parent;pagetitle;content1;content2;price;picture;picture-big;brand;");
		fwrite ($fp,"\n");
		fclose ($fp);
	}

	
	/* раздел */
	/* $parent = 18; */
	
	$content = getPageContent($url);
	$start_str = '<table class="cat">';$stop_str = '<div class="cat-controls">';
	$rule = "!".$start_str."(.*?)".$stop_str."!si";	preg_match($rule,$content,$content); $content = $content[0];
	
	for ($i=0; $i<10; $i++) {	
		
		/* парсим */
		parse($content = $content, $url = $url, $path = $path, $filename = $filename, $parent = $parent);

		/* вырезаем куски из контента удаляя блок */
		$pos = strpos($content, '<ul class="actions">');
		$content = substr($content, $pos, 120000);			
		if ($i<>0) {$content = substr($content, 1, 120000); }
	}	
}
?>