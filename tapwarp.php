<?php
/***************************************************************************
 *
 * This program provides a simple server side for receiving photos from 
 * Tap Warp, the photo uploading app.
 *
 * Copyright (C) 2016 QAON.NET
 * 
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 ***************************************************************************/

class TapWarpMsg {
	static public $TXT_UNAUTHED="Unauthenticated request.";
	static public $TXT_INVALID_AUTH_KEY="Invalid auth key.";
	static public $TXT_INVALID_FILE_KEY='Invalid file key.';
	static public $TXT_INVALID_ORDINAL='Invalid ordinal.';
	static public $TXT_INVALID_TOTAL='Invalid total.';
	static public $TXT_INVALID_FORMAT='Invalid format.';
	static public $TXT_ORDER_ERROR="Order error, %s";

	static public $fileUploadErrTexts=array(
		UPLOAD_ERR_OK=>'UPLOAD_ERR_OK',
		UPLOAD_ERR_INI_SIZE=>'Uploaded file is too large.',
		UPLOAD_ERR_FORM_SIZE=>'Uploaded file is too large.',
		UPLOAD_ERR_PARTIAL=>'UPLOAD_ERR_PARTIAL',
		UPLOAD_ERR_NO_FILE=>'UPLOAD_ERR_NO_FILE',
		UPLOAD_ERR_NO_TMP_DIR=>'UPLOAD_ERR_NO_TMP_DIR',
		UPLOAD_ERR_CANT_WRITE=>'UPLOAD_ERR_CANT_WRITE',
		UPLOAD_ERR_EXTENSION=>'UPLOAD_ERR_EXTENSION',
	);
}

class DefaultTapWarpHandler
{
	private $savetopath;
	private $fileMode=0666;
	private $dirMode=0775;

	public function __construct() {
		$this->savetopath=sys_get_temp_dir();
	}

	public function setSavePath($savetopath)
	{
		$this->savetopath=$savetopath;
		return $this;
	}

	public function getSavePath()
	{
		return $this->savetopath;
	}

	public function setFileMode($mode)
	{
		$this->fileMode=$mode;
		return $this;
	}

	public function getFileMode()
	{
		return $this->fileMode;
	}

	public function createDirForPath($path)
	{
        $dir=dirname($path);
        @mkdir($dir,$this->dirMode,true);
		@chmod($dir,$this->dirMode);
		@chgrp($dir,getmygid());
	}

	/** Get meta file path.
	 *  Same parameter should incur the same meta file path.
	 * \return The meta file path determined from arguments.
	 */
	public function getMetaFilePath($authKey,$fileKey)
	{
		$dir=$this->savetopath.DIRECTORY_SEPARATOR.$authKey;
		return $dir."/$fileKey.videopath";
	}

	/** Get file path for storing the uploaded file.
	 *  Same parameter should incur the same file path.
	 * \return The file path determined from arguments.
	 */
	public function getUploadFilePath($req)
	{
        list($category,$ext)=explode(DIRECTORY_SEPARATOR,$req->format);
        $dir=$this->savetopath.DIRECTORY_SEPARATOR.$req->authKey;
		if ($ext=='quicktime')
			$ext='quicktime.mov';
        return $dir.DIRECTORY_SEPARATOR.sprintf("%04d",$req->ordinal).".".$ext;
	}

	public function getImagePath($req)
	{
        list($category,$ext)=explode(DIRECTORY_SEPARATOR,$req->format);
        $dir=$this->savetopath.DIRECTORY_SEPARATOR.$req->authKey;
        return $dir.DIRECTORY_SEPARATOR.sprintf("%04d",$req->ordinal).".".$ext;
	}

	public function getMessagePath($req)
	{
        list($category,$ext)=explode(DIRECTORY_SEPARATOR,$req->format);
        $dir=$this->savetopath.DIRECTORY_SEPARATOR.$req->authKey;
        return $dir.DIRECTORY_SEPARATOR.sprintf("%04d",$req->ordinal).".$ext.message";
	}

	public function getEndPath($req)
	{
        $dir=$this->savetopath.DIRECTORY_SEPARATOR.$req->authKey;
		return $dir.DIRECTORY_SEPARATOR."END";
	}

	public function storeImage($imagepath,$imageB64)
	{
		$this->createDirForPath($imagepath);
		file_put_contents($imagepath,base64_decode($imageB64));
		chmod($imagepath,$this->fileMode);
	}

	public function storeMessage($msgfile,$message)
	{
		$this->createDirForPath($msgfile);
		file_put_contents($msgfile,$message);
        chmod($msgfile,$this->fileMode);
	}

	public function storeUploadFilePath($req)
	{
		$metapath=$this->getMetaFilePath($req->authKey,$req->fileKey);
		$videopath=$this->getUploadFilePath($req);
		$this->createDirForPath($metapath);
		file_put_contents($metapath,$videopath);
        chmod($metapath,$this->fileMode);
	}

	public function storeEndFile($endpath,$dataB64)
	{
		$this->createDirForPath($endpath);
		file_put_contents($endpath,base64_decode($dataB64));
        chmod($endpath,$this->fileMode);
	}

	public function restoreUploadFilePath($authKey,$fileKey)
	{
		$metapath=$this->getMetaFilePath($authKey,$fileKey);
		return @file_get_contents($metapath);
	}

    public function serve($req,$tw) {
		if (!$this->isAuthenticated($req))
		{
			$tw->respond("NG",TapWarpMsg::$TXT_UNAUTHED);
			return;
		}
        if ($req->ordinal>0)
        {
			$imagepath=null;
			$messagepath=null;
            list($category,$ext)=explode(DIRECTORY_SEPARATOR,$req->format);
            if ($category=='image')
            {
				$imagepath=$this->getImagePath($req);
				$this->storeImage($imagepath,$req->data);
            }
            elseif ($category=='video')
            {
				$this->storeUploadFilePath($req);
            }
            if ($req->message)
            {
				$messagepath=$this->getMessagePath($req);
				$this->storeMessage($messagefile,$message);
            }
			$this->onImageReceived($req->format,$ordinal,$imagepath,$messagepath);
        }
        else
        {
			$endpath=$this->getEndPath($req);
			$this->storeEndFile($endpath,$req->data);
			$this->onBatchEnded($req);
        }
        $tw->respond('OK');
    }

	public function moveUploadedFile($req,$uploadedfile,$movetofile)
	{
		if (move_uploaded_file($uploadedfile,$movetofile))
		{
			chmod($movetofile,0664);
			return $movetofile;
		}
		return false;
	}

	public function onFileReceived($req,$videopath)
	{
	}

	public function onFileFailed($req,$message)
	{
	}

	public function onImageReceived($req,$imagepath,$messagepath)
	{
	}

	public function onBatchEnded($req)
	{
	}

	public function isAuthenticated($req)
	{
		return true;
	}

	public function listImageFiles($authKey)
	{
		$arr=array();
		if (!preg_match('%^[0-9A-Za-z]+$%',$authKey))
			; // Do nothing
		else if (!file_exists($this->savetopath.DIRECTORY_SEPARATOR.$authKey.DIRECTORY_SEPARATOR.'END'))
			; // Do nothing
		else
		{
			$files=glob($this->savetopath.DIRECTORY_SEPARATOR.$authKey.DIRECTORY_SEPARATOR.'*.*');
			$arr=array();
			foreach ($files as $f)
			{
				if (substr($f,-10)=='.videometa')
					continue;
				if (substr($f,-10)=='.videopath')
					continue;
				if (substr($f,-8)=='.message')
					continue;
				if (preg_match('%^(.*)\.unprocessed\.(.*)$%',$f,$matches))
					continue;
				$o=new \stdClass;
				if (substr($f,-11)=='.percentage')
				{
					$f=substr($f,0,strlen($f)-11);
					$o->status='waiting';
				}
				$o->path=$f;
				$o->message=@file_get_contents($f.".message");
				$arr[]=$o;
			}   
		}
		return $arr;
	}

	public function queryPercentage($relFilePath)
	{
		if (!preg_match('%^[0-9A-Za-z]+/[0-9A-Za-z.-]+$%',$relFilePath))
			return null;

		$result=new stdClass;
		$result->status='unknown';
		$fullPath=$this->savetopath.DIRECTORY_SEPARATOR.$relFilePath;
		if (!file_exists($fullPath.".percentage"))
		{
			if (file_exists($fullPath))
				$result->status='complete';
		}
		else
		{
			$result->status='processing';
			$result->percentage=0+@file_get_contents($fullPath.".percentage");
		}
		return $result;
	}
}

class TapWarp
{
	public $serveHandlerObj;
	
	public function __construct($handlerObject=false)
	{
		if (!$handlerObject)
			$handlerObject=new DefaultTapWarpHandler();
		$this->serveHandlerObj=$handlerObject;
	}

	public function writeHeaders()
	{
		header('Access-Control-Allow-Origin: *');
		header('Access-Control-Allow-Headers: Content-Type, Content-Length, Content-Transfer-Encoding');
		header('Access-Control-Allow-Methods: POST');
		header('Access-Control-Max-Age: 864000');
	}

	public function serve()
	{
		$this->writeHeaders();
		if ($_SERVER['REQUEST_METHOD']!='POST')
			return;

		$content=file_get_contents('php://input');
		$req=json_decode($content);
		if (!preg_match('%^[0-9A-Za-z]{64,128}$%',$req->authKey))
			$this->respond('NG',TapWarpMsg::$TXT_INVALID_AUTH_KEY);
		else if (!preg_match('%^-?[0-9]+$%',$req->ordinal))
			$this->respond('NG',TapWarpMsg::$TXT_INVALID_ORDINAL);
		else if (!preg_match('%^-?[0-9]+$%',$req->total))
			$this->respond('NG',TapWarpMsg::$TXT_INVALID_TOTAL);
		else if ($req->format && !preg_match('%^-?[a-z]+(/[a-z0-9_-]+)?$%',$req->format))
			$this->respond('NG',TapWarpMsg::$TXT_INVALID_FORMAT);
		else if ($req->fileKey && !preg_match('%^[0-9a-zA-Z]+$%',$req->fileKey))
			$this->respond('NG',TapWarpMsg::$TXT_INVALID_FILE_KEY);
		else
			$this->serveHandlerObj->serve($req,$this);
	}

	public function respond($result,$message='')
	{
		header('Content-Type: application/json');
		$res=new stdClass;
		$res->result=$result;
		$res->message=$message;
		echo json_encode($res);
	}

	public function serveFile()
	{
		$savetopath=$this->serveHandlerObj->getSavePath();

		$this->writeHeaders();
		if ($_SERVER['REQUEST_METHOD']!='POST')
			return;

		$res=new stdClass;

		$res->result="OK";
		$res->message="";

		if (!preg_match('%^[0-9a-zA-Z]+$%',$authKey=$_POST['authKey']))
		{
			$res->result='NG';
			$res->message=TapWarpMsg::$TXT_INVALID_AUTH_KEY;
		}

		if ($res->result=='OK' && !preg_match('%^[0-9a-zA-Z]+$%',$fileKey=$_POST['fileKey']))
		{
			$res->result='NG';
			$res->message=TapWarpMsg::$TXT_INVALID_FILE_KEY;
		}

		if ($res->result=='OK' && $_FILES['fileData']['error'])
		{
			$res->result='NG';
			$res->message=TapWarpMsg::$fileUploadErrTexts[$_FILES['fileData']['error']+0];
		}

		if ($res->result=='OK' && $_FILES['fileData']['tmp_name'] && !$_FILES['fileData']['error'])
		{
			$dir=$savetopath.DIRECTORY_SEPARATOR.$authKey;
			$metapath=$dir."/$fileKey.videopath";
			$videopath=@file_get_contents($metapath);
			if (!$videopath)
			{
				@unlink($_FILES['fileData']['tmp_name']);
				$res->result='NG';
				$res->message=sprintf(TapWarpMsg::$TXT_ORDER_ERROR,$metapath);
			}
			else
			{
				if ($videopath=$this->serveHandlerObj->moveUploadedFile($req,$_FILES['fileData']['tmp_name'],$videopath))
				{
					@unlink($metapath);
				}	
				else
				{
					$lasterror=error_get_last();
					$res->message=$lasterror->message;
					$res->result='NG';
				}
			}
		}
		if ($res->result=='OK')
			$this->serveHandlerObj->onFileReceived($req,$videopath);
		else
			$this->serveHandlerObj->onFileFailed($req,$res->message);

		header("Content-Type: application/json");
		echo json_encode($res);

		@unlink($_FILES['fileData']['tmp_name']);
	}
}
