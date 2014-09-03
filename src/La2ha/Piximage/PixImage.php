<?
namespace La2ha\Piximage;

use \Image as Image;
use Predis\Command\HashExists;

/**
 * Class PixImage
 * @package La2ha\Piximage
 */
class PixImage
{

    /**
     *
     */
    function __construct()
    {

    }

    /**
     * @param $mode
     * @param $size
     * @param $path
     * @param bool $nocache
     * @return string
     */
    public function url($mode, $size, $path, $nocache = false)
    {
        if ($path{0} == '/')
            $path = substr($path, 1);
        return '/piximage/' . ($nocache ? 'nocache-' : '') . $mode . '/' . $size . '/' . $path;
    }

    /**
     * @param $mode
     * @param $size
     * @param $path
     * @return \Illuminate\Http\Response|mixed
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function stream($mode, $size, $path)
    {
        if ($path{0} == '/')
            $path = substr($path, 1);
        $cacheKey   = $mode . '/' . $size . '/' . $path;
        $path       = public_path($path);
        $modeOption = explode('-', $mode);
        if ($modeOption[0] == 'nocache') {
            \Config::set('piximage::cache', false);
            array_shift($modeOption);
        }

        $filetime = filemtime($path);
        $etag     = md5($filetime . $path);
        $time     = gmdate('r', $filetime);
        $expires  = gmdate('r', $filetime + \Config::get('piximage::lifetime'));
//        $length      = filesize($path);
        $headers = array(
            'Last-Modified' => $time,
            'Cache-Control' => 'must-revalidate',
            'Expires'       => $expires,
            'Pragma'        => 'public',
            'Etag'          => $etag,
        );

        $headerTest1 = isset($_SERVER['HTTP_IF_MODIFIED_SINCE']) && $_SERVER['HTTP_IF_MODIFIED_SINCE'] == $time;
        $headerTest2 = isset($_SERVER['HTTP_IF_NONE_MATCH']) && str_replace('"', '', stripslashes($_SERVER['HTTP_IF_NONE_MATCH'])) == $etag;

        if ($headerTest1 or $headerTest2) { //image is cached by the browser, we dont need to send it again
            return \Response::make('', 304, $headers);
        }

        if (\Config::get('piximage::cache') and \Cache::has($cacheKey))
            return \Cache::get($cacheKey);


        $mode    = $modeOption[0];
        $options = isset($modeOption[1]) ? $this->getOptions($modeOption[1]) : array();
        switch ($mode) {
            case 'resize':
                $image = $this->resize($options, $size, $path);
                break;
            case 'grab':
                $image = $this->fit($size, $path);
                break;
            case 'fit':
                $image = $this->fit($size, $path);
                break;
            default:
                throw new \Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
        }

        $headers = array_merge($headers, array(
//            'Content-Type' => File::mime(File::extension($path)),
            'Content-Type' => 'image/jpeg',
        ));

        $response = \Response::make($image->encode(null, 100), 200, $headers);
        if (\Config::get('piximage::cache'))
            \Cache::put($cacheKey, $response, \Config::get('piximage::cache_time'));
        return $response;
    }

    /**
     * @param $options
     * @param $size
     * @param $path
     * @return Image
     */
    protected function resize($options, $size, $path)
    {
        $size = $this->getSize($size);

        $width  = $size[0];
        $height = isset($size[1]) ? $size[1] : $width;
        $ratio  = isset($options['ratio']) ? $options['ratio'] : true;;
        $upsize = isset($options['upsize']) ? $options['upsize'] : false;

        $img = Image::make($path);
        $img->resize($width, $height, function ($constraint) use ($ratio, $upsize) {
            if($ratio)
                $constraint->aspectRatio();
            if (!$upsize)
                $constraint->upsize();

        });
        return $img;
    }

    /**
     * @param $size
     * @param $path
     * @return Image
     */
    protected function fit($size, $path)
    {
        $size = $this->getSize($size);


        $width  = $size[0];
        $height = isset($size[1]) ? $size[1] : $width;
        if ($width == '*' or $height == '*') {
            $size   = $this->getPropSize($path, $width, $height);
            $width  = $size[0];
            $height = $size[1];
        }
        $img = Image::make($path);
        $img->fit($width, $height);
        return $img;
    }

    /**
     * @param $size
     * @return array
     */
    protected function getSize($size)
    {
        return explode('x', $size);
    }

    /**
     * @param $path
     * @param null $width
     * @param null $height
     * @return array
     */
    protected function getPropSize($path, $width = null, $height = null)
    {
        $imagesize = getimagesize($path);
        if ($width == '*' and $height == '*') {
            return array(round($imagesize[0]), round($imagesize[1]));
        } elseif ($height == '*') {
            return array(round($width), round($imagesize[1] * $width / $imagesize[0]));
        } else {
            return array(round($imagesize[0] * $height / $imagesize[1]), round($height));
        }
    }

    /**
     * @param $options
     * @return array
     * Example: /piximage/nocache-resize-ratio,upsize:0/1000x900/storage/images/jobs/logo/c587db284184431a6c28d658fe8bb621.jpg
     */
    protected function getOptions($options)
    {
        $optArray = array();
        foreach (explode(',', $options) as $option) {
            $option               = explode(':', $option);
            $optArray[$option[0]] = isset($option[1]) ? $option[1] : true;
        }
        return $optArray;
    }

}