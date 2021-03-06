<?
function ErrCtl($EnvCtl,$LinkName,$OriginQuery,$InjectQuery,$OriginForm,$InjectForm,$FormMethod,$tAry){
	global $Env,$EnvAct;
	global $whitespace;
	global $AryXss;
	global $p_type,$reg_str_form,$reg_str_input,$p_typefile;//정규식관련

	//배열이 아니면 함수 종료
	if(!is_array($tAry)) return;

	$HeaderStatusCode=$tAry["HEADER"]["STATUSCODE"];

	$OriginMergeData=MergeQueryNForm($OriginQuery,$OriginForm);
	$InjectMergeData=MergeQueryNForm($InjectQuery,$InjectForm);

	//에러면 에러 링크에 저장 4xx,5xx
	if($EnvAct["ERR_2xx_YN"]=="Y" && eregi("[2][0-9]{2}",$HeaderStatusCode) &&$tAry["HEADER"][strtoupper("Content-Length")]!=0){
		ErrMsg($HeaderStatusCode,$FontColor="black");
		errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);
	//내용길이 분석해서 내용길이 없으면 404와 동일 취급
	}else if($EnvAct["ERR_4xx_YN"]=="Y" && $tAry["HEADER"][strtoupper("Content-Length")]==0){
		$HeaderStatusCode="404";
		ErrMsg($HeaderStatusCode,$FontColor="black");
		errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);
	}else if($EnvAct["ERR_4xx_YN"]=="Y" && eregi("[4][0-9]{2}",$HeaderStatusCode)){
		ErrMsg($HeaderStatusCode,$FontColor="black");
		errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);
	}else if($EnvAct["ERR_5xx_YN"]=="Y" && eregi("[5][0-9]{2}",$HeaderStatusCode)){
		ErrMsg($HeaderStatusCode,$FontColor="black");
		errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);
	}
	
	//파일 업로드 검색일때
	if($EnvAct["ERR_FILEFORM_YN"]=="Y" 
		&& eregi($reg_str_form,$tAry["BODY"]) 
		&& eregi($p_typefile,$tAry["BODY"])
		){
		$HeaderStatusCode="200FILE";
		ErrMsg($HeaderStatusCode,$FontColor="vilot");
		errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);		
	}

	//if($EnvAct["ERR_500SQL_YN"]=="Y" && eregi("[5][0-9]{2}",$HeaderStatusCode)){
	if($EnvAct["ERR_500SQL_YN"]=="Y"){
		//sql server에러
		if(eregi("varchar",$tAry["BODY"]) && eregi("",$tAry["BODY"]) ){
			$HeaderStatusCode="500SQLP";
			ErrMsg($HeaderStatusCode,$FontColor="red");
			errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);
		}else //sql server에러
		if(eregi("80040e14",$tAry["BODY"]) || eregi("SQL Server error",$tAry["BODY"]) ){
			$HeaderStatusCode="500SQL";
			ErrMsg($HeaderStatusCode,$FontColor="red");
			errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,$HeaderStatusCode,$FormMethod,$InjectQuery,$InjectForm);
		}
		//Access Driver 에러(취약점없음)
		if(eregi("Access Driver",$tAry["BODY"]) ){
			$HeaderStatusCode="500Access";
			ErrMsg($HeaderStatusCode,$FontColor="black");
		}
		//ADODB 800a0d5d 에러(취약점없음)
		if(eregi("800a0d5d",$tAry["BODY"]) || eregi("ADODB",$tAry["BODY"]) ){
			$HeaderStatusCode="500ADO";
			ErrMsg($HeaderStatusCode,$FontColor="black");
		}
		//ADODB 800a0d5d 에러(취약점없음)
		if(eregi("JET Database",$tAry["BODY"]) ){
			$HeaderStatusCode="500JET";
			ErrMsg($HeaderStatusCode,$FontColor="black");
		}
	}

	//xss에러 검색
	if(!is_null($EnvCtl["XssLinkDepth"]) && $EnvCtl["XssLinkDepth"]>0 && $EnvAct["ERR_200XSS_YN"]){
		//echo "<BR>Xss검사시작";
		foreach ($AryXss as $Str => $Reg){
			if(eregi($Reg,$tAry["BODY"])){
				ErrMsg($HeaderStatusCode="200XSS",$FontColor="black");
				errLink($EnvCtl,$LinkName,"Y",$OriginMergeData,$InjectMergeData,"200XSS",$FormMethod,$InjectQuery,$InjectForm);
			}
		}
	}
}



//500에러 링크 모음
function errLink($EnvCtl,$tLink,$ParamYN,$OriginMergeData,$InjectMergeData,$ErrCode,$FormMethod,$InjectQuery,$InjectForm){
	global $Env;
	global $errLink;

	if($Env["f_debug_yn"]){
		echo "<BR><BR>errLink";
		echo "<BR>tLink:".$tLink;
		echo "<BR>$ParamYN:".$ParamYN;
		echo "<BR>OriginMergeData:".$OriginMergeData;
		echo "<BR>ErrQueryString:".$ErrQueryString;
		echo "<BR>Parent_doc:".$EnvCtl["Parent_doc"];
	}

	//기존에 존재 하는지 검사
	for($j=0;$j<count($errLink);$j++){
		if(strtoupper($errLink[$j][0])==strtoupper($tLink)){
			//파라미터가 존재하면 넣기
			if( ($ParamYN=="Y"||$errLink[$j][1]=="Y")){
				//이미 기존의 에러코드와 파라미터가 동일한 링크가 있으면 통과
				for($t=0;$t<count($errLink[$j][3]);$t++)
					if($ErrCode==$errLink[$j][3][$t][1] && $InjectMergeData==$errLink[$j][3][$t][0])return;
				//echo " 에러링크(파람)존재 ";

				$errLink[$j][1]="Y";
			}else if($ParamYN=="N" && $errLink[$j][1]=="N"){
				//파라미터가 없을경우 에러코드가 다를경우 추가
				for($t=0;$t<count($errLink[$j][3]);$t++)
					if($ErrCode==$errLink[$j][3][$t][1])return;
				//echo " 에러링크존재 ";

				$errLink[$j][1]="N";
			}

			//에러코드 같은것까지 없으면 추가
			if($Env["f_debug_yn"])echo "<BR>에러링크 추가1:".$EnvCtl["Parent_doc"];

			$errLink[$j][2]++;
			$errLink[$j][3][count($errLink[$j][3])]=array($InjectMergeData,$ErrCode,$InjectQuery,$InjectForm);
			$errLink[$j][4]=$EnvCtl["Parent_doc"];
			$errLink[$j][5]=$OriginMergeData;
			$errLink[$j][6]=$FormMethod;

			return;
		}
	}
	//링크명,파라미터여부,파라미터체크수,쿼리스트링배열
	if($Env["f_debug_yn"])echo "<BR>에러링크 신규2:".$EnvCtl["Parent_doc"];
	//echo "<BR>$ErrCode 추가";	
	$errLink[count($errLink)]=array($tLink,$ParamYN,1,array(array($InjectMergeData,$ErrCode,$InjectQuery,$InjectForm)),$EnvCtl["Parent_doc"],$OriginMergeData,$FormMethod);
	//echo "\n아웃링크 사이즈 :".count($errLink);
}
?>