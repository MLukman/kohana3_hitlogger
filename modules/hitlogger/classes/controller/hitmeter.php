<?php defined('SYSPATH') or die('No direct script access.');

class Controller_HitMeter extends Controller {

	public function action_index()
	{
		$imgpath = HitLogger::config('num_images_path');
		$imgext = HitLogger::config('num_images_ext');
		list($hit) = HitLogger::get_hits(Arr::get($_GET, 'u', ''));
		$hit = abs(floatval($hit));
		$len = max(8, strlen(strval($hit)));
		$nums = str_split(sprintf("%0{$len}.0f", $hit));
		$imgs = array();
		$img_dims = array();
		$height = 0;
		$width = 0;
		$imagecreatefun = "imagecreatefrom$imgext";
		foreach ($nums as $num) {
			if (!isset($imgs[$num])) {
				$imgs[$num] = $imagecreatefun($imgpath.$num.'.'.$imgext);
				$img_dims[$num] = array(
					'x' => imagesx($imgs[$num]),
					'y' => imagesy($imgs[$num]),
				);
				$height = max($height, $img_dims[$num]['y']);
			}
			$width += $img_dims[$num]['x'];
		}

		$img = imagecreate($width, $height);
		$x = 0;
		foreach ($nums as $num) {
			imagecopy($img, $imgs[$num], $x, 0, 0, 0, $img_dims[$num]['x'], $img_dims[$num]['y']);
			$x += $img_dims[$num]['x'];
		}
		header ('Content-type: image/png');
		imagepng($img);
		imagedestroy($img);
		exit;
	}
}