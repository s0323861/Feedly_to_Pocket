<?php

// コンシューマーキー
$consumer_key = '****************************';

// リクエストURL
$request_url = 'https://getpocket.com/v3/send';

// メソッド
$request_method = 'POST';

// アクセストークンを取得できたかどうか判定する
if($_GET){

  $access_token = $_GET["ac"];
  $error = $_GET["error"];

  // 失敗
  if( !empty($error) )
  {
    $result2 = '<div class="bs-component">';
    $result2 .= '<div class="alert alert-dismissible alert-danger">';
    $result2 .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    $result2 .= '<strong>Oh snap!</strong> <span class="alert-link">' . urlencode($error) . '</span> Try submitting again.';
    $result2 .= '</div>';
    $result2 .= '</div>';

  // アクセストークンが取得できた場合
  }else{

    // ファイル名を取得する
    $fn_tmp = substr($_GET["fname"], 0, strpos($_GET["fname"], "return==1"));

    $filename = "./tmp/" . $fn_tmp;

    $json = file_get_contents($filename);

    // 先頭3バイトのBOMコードを除去して同名で保存
    if(preg_match("/^efbbbf/", bin2hex($json[0] . $json[1] . $json[2])) === 1) {
      $json = substr($json, 3);
      file_put_contents($filename, $json);
    }

    $json = mb_convert_encoding($json, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');

    $import_array = json_decode($json, true);

    for($i=0; $i < count($import_array); $i++){
      $import_array[$i]['action'] = 'add';
      unset($import_array[$i]['title']);

      $time_array = explode(" ", $import_array[$i]['time']);
      $import_array[$i]['time'] = strtotime($time_array[1] . " " . $time_array[2] . " " . $time_array[3] . " " . $time_array[4]);
    }

    // 多次元の連想配列をJSONデータに変換する
    $actions_json = json_encode($import_array);

    // パラメーター
    $params = array(
      // アクションのJSON
      'actions' => $actions_json,
    );
   
    // パラメーターにコンシューマーキーとアクセストークンを追加
    $params = array_merge( $params, array( 'consumer_key' => $consumer_key, 'access_token' => $access_token, ) );

    // コンテキスト
    $context = array(
      'http' => array(
        'method' => $request_method,
        'content' => http_build_query( $params ),
      )
    );

    // アイテムデータをJSON形式で取得する (CURLを使用)
    $curl = curl_init();

    // オプションのセット
    curl_setopt( $curl, CURLOPT_URL, $request_url );
    curl_setopt( $curl, CURLOPT_HEADER, 1 );
    // メソッド
    curl_setopt( $curl, CURLOPT_CUSTOMREQUEST, $context['http']['method'] );
    // 証明書の検証を行わない
    curl_setopt( $curl, CURLOPT_SSL_VERIFYPEER, false );
    // curl_execの結果を文字列で返す
    curl_setopt( $curl, CURLOPT_RETURNTRANSFER, true );
    // リクエストボディ
    curl_setopt( $curl, CURLOPT_POSTFIELDS, $context['http']['content'] );
    // タイムアウトの秒数
    curl_setopt( $curl, CURLOPT_TIMEOUT, 5 );

    // 実行
    $res1 = curl_exec( $curl );
    $res2 = curl_getinfo( $curl );

    // 終了
    curl_close( $curl );

    // 取得したデータ(JSONなど)
    $json = substr( $res1, $res2['header_size'] );

     // レスポンスヘッダー (検証に利用したい場合にどうぞ)
    $header = substr( $res1, 0, $res2['header_size'] );

    // HTML用
    $html = '';

    // JSONデータをオブジェクト形式に変換する
    $obj = json_decode( $json );

    // インポートに失敗
    if( !isset($obj->status) || !$obj->status ){
      $html = '<div class="bs-component">';
      $html .= '<div class="alert alert-dismissible alert-danger">';
      $html .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
      $html .= '<strong>Oh snap!</strong> <a href="#" class="alert-link">リクエストに失敗しました…</a> Try submitting again.';
      $html .= '</div>';
      $html .= '</div>';
 
    }else{


      // 結果の作成
      $html = '<div class="bs-component">';
      $html .= '<div class="alert alert-dismissible alert-success">';
      $html .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
      $html .= '<strong>Well done!</strong> リクエストに成功しました。';
      $html .= '</div>';
      $html .= '</div>';

      // アプリケーション連携の解除
      $html .= '<div class="bs-component">';
      $html .= '<div class="alert alert-dismissible alert-info">';
      $html .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
      $html .= '<strong>アプリケーション連携の解除</strong> このアプリケーションとの連携は、<a href="https://getpocket.com/connected_applications" class="alert-link" target="_blank">設定ページ</a>で解除することができます。';
      $html .= '</div>';
      $html .= '</div>';

    }

    unlink($filename);



  }

// ファイルのアップロード時の処理
}elseif(is_uploaded_file($_FILES['upfile']['tmp_name'])){

  $finfo = new finfo(FILEINFO_MIME_TYPE);

  if (!isset($_FILES['upfile']['error']) || !is_int($_FILES['upfile']['error'])) {
    $error = "パラメータが不正です (parameter is incorrect.)";
  }elseif ($_FILES['upfile']['size'] > 1000000) {
    $error = "ファイルサイズが大きすぎます (file size is too large.)";
  }elseif (!$ext = array_search(
    $finfo->file($_FILES['upfile']['tmp_name']),
    array('txt' => 'text/plain'), true)) {
    $error = "ファイル形式が不正です (file format is incorrect.)";
  }

  if(empty($error)){

    // ファイル名の文字化け対策
    $fname = $_FILES['upfile']['name'];
    $fname = mb_convert_encoding($fname, 'UTF-8', 'auto');

    if (move_uploaded_file($_FILES['upfile']['tmp_name'], "tmp/" . $fname)) {
      chmod("tmp/" . $fname, 0644);

      // pocketの認証へ
      header( 'Location: ./pocket_oauth.php?fname=' . $fname );

    }

  }

  // アップロードエラー
  if($error != ""){
    // 結果の作成
    $html = '<div class="bs-component">';
    $html .= '<div class="alert alert-dismissible alert-danger">';
    $html .= '<button type="button" class="close" data-dismiss="alert">&times;</button>';
    $html .= '<strong>Oh snap!</strong> <span class="alert-link">アップロードに失敗しました…</span> ' . $error;
    $html .= '</div>';
    $html .= '</div>';
  }

}
?>
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="description" content="Grab items that have been marked 'Saved for later' in Feedly and push them to Pocket">
  <meta name="keywords" content="feedly,export,import,pocket,online,tool">
  <meta name="author" content="Akira Mukai">
  <title>FeedlyのSaved For LaterをPocketへインポートする</title>
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/css/bootstrap.min.css">
  <link rel="stylesheet" href="//maxcdn.bootstrapcdn.com/font-awesome/4.5.0/css/font-awesome.min.css">

  <style type="text/css">
  body { padding-top: 80px; }
  @media ( min-width: 768px ) {
    #banner {
      min-height: 300px;
      border-bottom: none;
    }
    .bs-docs-section {
      margin-top: 8em;
    }
    .bs-component {
      position: relative;
    }
    .bs-component .modal {
      position: relative;
      top: auto;
      right: auto;
      left: auto;
      bottom: auto;
      z-index: 1;
      display: block;
    }
    .bs-component .modal-dialog {
      width: 90%;
    }
    .bs-component .popover {
      position: relative;
      display: inline-block;
      width: 220px;
      margin: 20px;
    }
    .nav-tabs {
      margin-bottom: 15px;
    }
    .progress {
      margin-bottom: 10px;
    }
  }
  </style>

  <!--[if lt IE 9]>
    <script src="//oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="//oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->

</head>
<body>
<header>
  <div class="navbar navbar-default navbar-fixed-top">
    <div class="container">
      <div class="navbar-header">
        <a href="./" class="navbar-brand"><i class="fa fa-flask"></i> お役立ちツール</a>
        <button class="navbar-toggle" type="button" data-toggle="collapse" data-target="#navbar-main">
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
          <span class="icon-bar"></span>
        </button>
      </div>
      <div class="navbar-collapse collapse" id="navbar-main">
      </div>
    </div>
  </div>
</header>

<div class="container">

  <div class="row">

    <!-- Blog Entries Column -->
    <div class="col-lg-12">

    <h1 class="page-header">
    <i class="fa fa-wrench"></i> Feedly to Pocket<br>
    <small>Semiautomatic export of Feedly &#39;Saved For Later&#39; and import to getpocket.com</small>
    </h1>

    </div>

  </div>


<?php
if(!empty($html)){
  echo <<<EOM
  <div class="row">

    <div class="col-lg-12">

{$html}

    </div>

  </div>
EOM;
}
?>

<?php
if(!empty($result2)){
  echo <<<EOM
  <div class="row">

    <div class="col-lg-12">

{$result2}

    </div>

  </div>
EOM;
}
?>

  <div class="row">

    <div class="col-lg-12">

      <p>
      最初にFeedlyの「Saved For Later」のデータをエクスポートして、次にそのデータをPocketへインポートするという手順になります。<br>
      </p>

      <h2><span class="label label-danger">STEP 1</span></h2>

      <ol>
      <li>Google Chromeを開いてください。<br>Open up Google Chrome</li>
      <li>Feedlyにログインして「Saved For Later」を表示してください。<br>Login to Feedly and go to the "Saved For Later" list.</li>
      <li>ページの一番下までスクロールして全てのリストを表示させてください。<br>Keep scrolling down the page until all saved documents have been loaded</li>
      <li>画面上で右クリックしてメニュー「検証(I)」をクリックしてください。<br>Right click on the page and select "Inspect Element"</li>
      <li>デベロッパーツール内の「コンソール」タブをクリックしてください。<br>Inside the "Inspector" tool, click the "Console" tab.</li>
      <li>コンソール内に以下のスクリプトを貼り付けてください。<br>Paste the script below into the console</li>
      </ol>

      <div class="bs-component">
          <div class="alert alert-dismissible alert-danger">
          <strong>注意 (NOTE)</strong> HTTPS通信ではjQueryは動きません。<br><a href="#" class="alert-link">You must switch off SSL (http rather than https)</a> or jQuery won&#39;t load!
          </div>
      </div>

<pre class="prettyprint linenums:1"><code class="language-js">function loadJQuery(){
  script = document.createElement('script');
  script.setAttribute('src', 'http://code.jquery.com/jquery-latest.min.js');
  script.setAttribute('type', 'text/javascript');
  script.onload = loadSaveAs;
  document.getElementsByTagName('head')[0].appendChild(script);
}
function loadSaveAs(){
  saveAsScript = document.createElement('script');
  saveAsScript.setAttribute('src', 'https://rawgit.com/eligrey/FileSaver.js/master/FileSaver.js');
  saveAsScript.setAttribute('type', 'text/javascript');
  saveAsScript.onload = saveToFile;
  document.getElementsByTagName('head')[0].appendChild(saveAsScript);
}
function saveToFile() {
  // Loop through the DOM, grabbing the information from each bookmark
  map = jQuery("#section0_column0 div.u0Entry").map(function(i, el) {
    var $el = jQuery(el);
    var regex = /published:(.*)\ --/i;
    return {
      title: $el.data("title"),
      url: $el.data("alternate-link"),
      time: regex.exec($el.find("div.lastModified span").attr("title"))[1]
    };
  }).get(); //  Convert jQuery object into an array
  // Convert to a nicely indented JSON string
  json = JSON.stringify(map, undefined, 2);
var blob = new Blob([json], {type: "text/plain;charset=utf-8"});
saveAs(blob, "FeedlySavedForLater" + Date.now().toString() + ".txt");
}
loadJQuery()</code></pre>

      <p>スクリプトが実行されると以下のようなフォーマットのファイルがダウンロードされます。<br>
      Format of JSON is as follows.</p>

<pre class="prettyprint linenums:1"> [
   {
     title: "Title",
     url: "www.example.com/title",
     time: "Sunday "
   }
 ]</pre>

    </div>

  </div>

  <div class="row">

    <div class="col-lg-12">

      <h2><span class="label label-danger">STEP 2</span></h2>

      <form action="./" method="post" enctype="multipart/form-data">
      <fieldset>

      <div class="well bs-component">

        <div class="form-group">
          <label for="inputSubject" class="control-label">「STEP 1」で作成したファイルを選択してください。 (Select .txt file)</label>
          <input type="file" name="upfile" class="form-control" id="inputSubject" value="<?php echo $_POST["upfile"]; ?>" required>
        </div>

        <div class="form-group">
          <button type="submit" class="btn btn-primary"><i class="fa fa-wrench"></i> インポートする (Import to Pocket)</button>
        </div>

      </div>

      </fieldset>
      </form>

    </div>

  </div>

  <hr>

  <footer class="footer">

    <p>
    Thanks to <a href="https://gist.github.com/ShockwaveNN/a0baf2ca26d1711f10e2" target="_blank">Pavel Lobashov</a>.<br><br>
    Copyright (C) 2016 <a href="http://tsukuba42195.top/">Akira Mukai</a><br>
    Released under the MIT license<br>
    <a href="http://opensource.org/licenses/mit-license.php" target="_blank">http://opensource.org/licenses/mit-license.php</a>
    </p>

  </footer>
  <!-- /footer -->


</div>

<script src="//ajax.googleapis.com/ajax/libs/jquery/2.1.1/jquery.min.js"></script>
<script src="//maxcdn.bootstrapcdn.com/bootstrap/3.3.6/js/bootstrap.min.js"></script>
<script src="//cdn.rawgit.com/google/code-prettify/master/loader/run_prettify.js"></script>

<script type="text/javascript">
$(function(){

});
</script>

</body>
</html>
