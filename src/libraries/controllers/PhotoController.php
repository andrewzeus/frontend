<?php
/**
  * Photo controller for HTML endpoints.
  * 
  * @author Jaisen Mathai <jaisen@jmathai.com>
 */
class PhotoController extends BaseController
{ 
  /**
    * Create a new version of the photo with ID $id as specified by $width, $height and $options.
    *
    * @param string $id ID of the photo to create a new version of.
    * @param string $hash Hash to validate this request before creating photo.
    * @param int $width The width of the photo to which this URL points.
    * @param int $height The height of the photo to which this URL points.
    * @param int $options The options of the photo wo which this URL points.
    * @return string HTML
    */
  public static function create($id, $hash, $width, $height, $options = null)
  {
    $args = func_get_args();
    // TODO, this should call a method in the API
    $photo = Photo::generateImage($id, $hash, $width, $height, $options);
    // TODO return 404 graphic
    if($photo)
    {
      header('Content-Type: image/jpeg');
      readfile($photo);
      unlink($photo);
      return;
    }
    echo 'did not work';
  }

  /**
    * Delete a photo specified by the ID.
    *
    * @param string $id ID of the photo to be deleted.
    * @return void HTTP redirect
    */
  public static function delete($id)
  {
    $delete = getApi()->invoke("/photo/{$id}/delete.json", EpiRoute::httpPost);
    if($delete['result'] !== false)
      getRoute()->redirect('/photos?deleteSuccess');
    else
      getRoute()->redirect('/photos?deleteFailure');
  }

  /**
    * Render the photo page for a photo with ID $id.
    * If $options are present then it will render that photo.
    *
    * @param string $id ID of the photo to be deleted.
    * @param string $options Optional options for rendering this photo.
    * @return string HTML
    */
  public static function photo($id, $options = null)
  {
    $apiResp = getApi()->invoke("/photo/{$id}.json", EpiRoute::httpGet, array('_GET' => array('actions' => 'true', 'returnSizes' => getConfig()->get('photo')->detailSize)));
    if($apiResp['code'] == 200)
    {
      $detailDimensions = explode('x', getConfig()->get('photo')->detailSize);
      $apiNextPrevious = getApi()->invoke("/photo/nextprevious/{$id}.json", EpiRoute::httpGet, array('_GET' => array('returnSizes' => getConfig()->get('photo')->nextPreviousSize)));
      $photo = $apiResp['result'];
      if($photo['width'] >= $photo['height'])
      {
        $photo['thisWidth'] = $detailDimensions[0];
        $photo['thisHeight'] = intval($photo['height']/$photo['width']*$detailDimensions[0]);
      }
      else
      {
        $photo['thisWidth'] = intval($photo['width']/$photo['height']*$detailDimensions[1]);
        $photo['thisHeight'] = $detailDimensions[1];
      }
      $photo['previous'] = isset($apiNextPrevious['result']['previous']) ? $apiNextPrevious['result']['previous'] : null;
      $photo['next'] = isset($apiNextPrevious['result']['next']) ? $apiNextPrevious['result']['next'] : null;
      $body = getTheme()->get('photo-details.php', array('photo' => $photo));
      getTheme()->display('template.php', array('body' => $body, 'class' => 'photo-details'));
    }
    else
    {
      echo "Couldn't find photo {$id}"; // TODO
    }
  }

  /**
    * Render a list of the user's photos as specified by optional $filterOpts.
    * If $options are present then it will apply those filter rules.
    *
    * @param string $filterOpts Optional options for filtering
    * @return string HTML
    */
  public static function photos($filterOpts = null)
  {
    if($filterOpts)
      $photos = getApi()->invoke("/photos/{$filterOpts}.json", EpiRoute::httpGet, array('_GET' => array('returnSizes' => getConfig()->get('photo')->thumbnailSize)));
    else
      $photos = getApi()->invoke("/photos.json", EpiRoute::httpGet, array('_GET' => array('returnSizes' => getConfig()->get('photo')->thumbnailSize)));

    $photos = $photos['result'];

    $pagination = array('requestUri' => $_SERVER['REQUEST_URI'], 'currentPage' => $photos[0]['currentPage'], 
      'pageSize' => $photos[0]['pageSize'], 'totalPages' => $photos[0]['totalPages']);

    $body = getTheme()->get('photos.php', array('photos' => $photos, 'pagination' => $pagination));
    getTheme()->display('template.php', array('body' => $body, 'class' => 'photos'));
  }

  /**
    * Update a photo's data in the datastore.
    * Attributes to update are in _POST.
    *
    * @param string $id ID of the photo to update.
    * @return void HTTP redirect
    */
  public static function update($id)
  {
    $status = getApi()->invoke("/photo/{$id}.json", EpiRoute::httpPost);
    // TODO include success/error paramter
    getRoute()->redirect("/photo/{$id}");
  }

  /**
    * Display the upload form for photos.
    *
    * @return string HTML
    */
  public static function upload()
  {
    if(!User::isOwner())
    {
      getTemplate()->display('template.php', array('body' => getTemplate()->get('noPermission.php')));
      return;
    }
    $body = getTemplate()->get('upload.php');
    $js = getTemplate()->get('js/upload.js.php');
    getTemplate()->display('template.php', array('body' => $body, 'js' => $js, 'jsFiles' => array('/assets/js/plugins/jquery-ui.widget.js','/assets/js/plugins/jquery.fileupload.js')));
  }

  /**
    * Update a photo's data in the datastore.
    * Attributes to update are in _POST.
    *
    * @param string $id ID of the photo to update.
    * @return void HTTP redirect
    */
  public static function uploadPost()
  {
    $upload = getApi()->invoke('/photo/upload.json', EpiRoute::httpPost, array('_FILES' => $_FILES, '_POST' => $_POST));
    if($upload['result'])
      getRoute()->redirect('/photos?uploadSuccess');
    else
      getRoute()->redirect('/photos?uploadFailure');
  }
}
