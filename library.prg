************************************************************************
********** Function Traloi(cMess, cTitle, cCaption1, cCaption2, cCaption3)
********** cMess 	:	Loi nhan de dua ra
********** cTitle	:	Ten tieu de cua form
********** cCaption1:	Tieu de cua nut bam1(cmdOK)
********** cCaption2:	Tieu de cua nut bam2(cmdCancel)
********** cCaption3:	Tieu de cua nut bam3(...)
********** Dua ra mot loi hoi dap, chon co hay khong
************************************************************************
Function Traloi
Lparameter cMess, cTitle, cCaption1, cCaption2
Public __nValueRETURN
#Region 0
Region nVal, nPara,_cPara, _cForm
nPara=Parameter()
Do case
	Case nPara=5
		_cPara="'"+cMess+"','"+cTitle+"','"+cCaption1+"','"+ cCaption2+;
			"','"+ cCaption3+"'"
	Case nPara=4
		_cPara="'"+cMess+"','"+cTitle+"','"+cCaption1+"','"+ cCaption2+"'"
	Case nPara=3
		_cPara="'"+cMess+"','"+cTitle+"','"+cCaption1+"'"
	Case nPara=2
		_cPara="'"+cMess+"','"+cTitle+"'"	
	Case nPara=1
		_cPara="'"+cMess+"'"
	Otherwise	
		Return 0
Endcase	
_cForm=CreateObject("FrmMessage",&_cPara)
_cForm.Show
*Read events
nVal=__nValueRETURN
Release __nValueRETURN
Return nVal
************************************************************************
********** Function Run_form(cTitle)
********** cTitle 	:	Ten form can chay
**********	Chuc nang:	Chay mot form nao do
************************************************************************
Procedure Run_form
Parameter cTitle	
Private cMess
	If !File("Quanlybaihat.Ini")
		cMess=	" Tho¸t ngay khái ch­¬ng tr×nh."+Chr(13)+;
				" B¹n ®ang truy cËp tr¸i phÐp vµo ch­¬ng tr×nh"+;
				Chr(13)	+" B¹n h·y liªn hÖ víi t¸c gi¶ ®Ó biÕt thªm chi tiÕt"		
		Thongbaoloi(cMess)
		Quit
	Endif
	If !Used("Quanlybaihat")
		Sele 0
		use Quanlybaihat.Ini 
	Endif
	Sele("Quanlybaihat")	
	Locate for Allt(Run_Pro)==Allt(cTitle)
	If Found()
		cDo=Allt(Command)
		cPara=Allt(Parameter)		
		If !Empty(cPara)
			cDo=cDo+" With "+cPara
		Endif		
		&cDo
		*Read Events
	Endif
	If Used("Quanlybaihat")
		Sele("Quanlybaihat")
		Use
	Endif	
Return
************************************************************************
************	Function  Verifyfile(cAlias) 
************	cAlias:	Ten alias de kiem tra
************	Return:	True neu alias nay dang su dung hoac co file alias.Dbf, 
************			False nguoc lai
************************************************************************
Function  Verifyfile
Parameter _cAlias1 , cPath1
Local  _cAlias, cFile, cIndex
cIndex=""
_cAlias=Allt(Upper(_cAlias1))

If !Empty(cPath1)
	cFile=Allt(Upper(cPath1))+"\"+_cAlias
Else
	cFile=_cAlias
Endif

If !Used(_cAlias) then
	If !File(cFile+".DBF") then
		Thongbaoloi("TÖp " +Sys(2003)+"\"+Allt(cFile)+".Dbf  kh«ng tån t¹i trªn ®Üa."+chr(13)+;
					"H·y liªn hÖ víi t¸c gi¶ ®Ó biÕt thªm chi tiÕt")
		Return .F.
	Endif
	If File(cFile+".CDX")		
		cIndex=" Index " +_cAlias
	Endif
	Sele 0
	Use(cFile) &cIndex
	If !Empty(cIndex)
		Set Order To 1
	Endif
Endif	
Sele(_cAlias)	
Return .T.
*************************************************************************
*********	Procedure closefile(calias)
*********	calias:	Tep can dong lai
*********	Chuc nang:	Dong tep du lieu dang mo lai
*************************************************************************
PROCEDURE closefile
Lparameter calias
If Used(cAlias)
	Use In (cAlias)
Endif
Return
*************************************************************************
*********	Procedure reuse(calias)
*********	calias:	Tep can mo lai
*********	Chuc nang:	Mo lai tep da mo
*************************************************************************
PROCEDURE reuse
Lparameter calias
=closefile(calias)
=Verifyfile(calias)
Return
*************************************************************************
*********	Procedure Thongbaoloi(cMess)
*********	cMess:	Loi thong bao de dua ra	
*********	Chuc nang:	Dua ra mot cau thong bao
*************************************************************************
Procedure Thongbaoloi
Parameters cMess1,cmdCaption1
*frmThongbao=CreateObject("FrmThongbaoloi")		
*frmThongbao.LblMess.Caption=cMess
*frmThongbao.Visible=.T.
Do Form frmwarn With cMess1,cmdCaption1
Return

*************************************************************************
********	Function CorrectName(cDocument)
********	cDocument: Doan van can nan ten	
********	Chuc nang: Tra lai doan van duoc nan theo: Cac chu cai dau
********			   la hoa, ac chu cai sau la thuong
************************************************************************
Function CorrectName
Lparameter cDocument
Private  cCorrectName, cTen, nPos,nLen
cTen=Allt(cDocument)
If Empty(cTen)  Then
	Return ""
Endif

nLen=Len(cTen)
If nLen=1 Then
	Return Upcase(cTen)
Endif
cCorrectName=""
nPos=1
Do while nPos<=nLen &&Cac tu con lai
	Do while Substr(cTen,nPos, 1)=" " 
		nPos=nPos+1
	Enddo
	
	cCorrectName=cCorrectName+" "+Upcase(Substr(cTen,nPos,1))&&Tu au tien
	nPos=nPos+1
	Do while Substr(cTen,nPos, 1)<>" " And nPos<=nLen&&Cac tu can lai
		cCorrectName=cCorrectName+Lowcase(Substr(cTen,nPos,1))
		nPos=nPos+1
	Enddo

Enddo
Return Ltrim(cCorrectName)


************************************************************************
********	Function MakeCode(cDocument)
********	cDocument: Doan van can tao ma	
********	Chuc nang: Tra lai doam ma bao gom cac chu cai dau 
********			   cua doan van dua vao
************************************************************************
Function MakeCode_All
Lparameter cDocument
Private  cMai, cTen, nIndex, nPos,nLen
nIndex=1
nPos=1
cTen=Allt(cDocument)
nLen=Len(cTen)
cMa=Substr(cTen, 1, 1)
Do while nPos>0
	nPos=At(" ",cTen,nIndex)		
	If nPos=0 Then
		Loop
	Endif
	cMa=cMa+Substr(cTen, nPos+1, 1)
	nIndex=nIndex+1
Enddo
Return cMa
************************************************************************
********	Function MakeCode(cDocument)
********	cDocument: Doan van can tao ma	
********	Chuc nang: Tra lai chu cai dau tien hoa 
************************************************************************
Function MakeCode
Lparameter cPre, cAlias, cID, cMax
Private Savealias, cma, i, cPre

Savealias=Alias()
Sele(cAlias)
If Recc(calias)=0 Then
	Return cPre+Right(REPLICATE("0",LEN(cMAx)-1)+"1",LEN(cMax) )
ENDIF

Dime aMa(1)
Select Max(Val( Right(&cID, LEN(cMax)))) From (cAlias); 
	Where &cID=cPre into Array aMa			
If Empty(aMa)	
	Return cPre+Right(REPLICATE("0",LEN(cMAx)-1)+"1",LEN(cMax) )
ELSE && Neu ma tim thay nho hon max 
	IF aMa<VAL(cMax )
		cMa=cPre+Right(REPLICATE("0",LEN(cMAx)-1)+LTrim(Str(aMa+1)),LEN(cMax) )
	ELSE && Neu ma=Max thi duyet tu dau
		cPre=REPLICATE("0",LEN(cMAx)-1)
		FOR i=1 TO VAL(cMax)-1
			SELECT(cAlias )
			cMa=cMa+Right(cPre+Ltrim(Str(i)),LEN(cMax) )
			LOCATE FOR &cID==cMa	 
			IF NOT FOUND()
				EXIT					
			ENDIF			
		NEXT				
	Endif	
ENDIF
IF EMPTY(cMa)
	cMA="E"
ENDIF

if !empty(SaveAlias)
	Sele(SaveAlias)
Endif
Return cMa

************************************************************************
********	Function Upcase(cKey)
********	cKey: Ky tu 
********	Chuc nang: Tra lai chu hoa cua 1 ky tu 
************************************************************************
Function Upcase
Lparameter cKey
If Len(cKey)<> 1 Then
	*Bao loi
	Thongbaoloi("Xin lçi, hµm nµy chØ dïng víi mét ký tù")	
	Return ""
Endif

Do case
	Case cKey="ë"
		Return "ë"	
	Case cKey="ó"
		Return "ó"	
	Case cKey="Ð"
		Return "Ð"	
				
	Case cKey="©"
		Return "¢"		
	Case cKey="¨"
		Return "¡"
		 
	Case cKey="®"
		Return "§"
		
	Case cKey="ª"
		Return "£"
		
	Case cKey="«"
		Return "¤"	
	Case cKey="¬"
		Return "¥"
	Case cKey="­"
		Return "¦"
	Case isAlpha(cKey)	
		Return Upper(cKey)
	Other 
		Return cKey
Endcase
************************************************************************
********	Function LowCase(cKey)
********	cKey: Ky tu 
********	Chuc nang: Tra lai chu thuong cua 1 ky tu 
************************************************************************
Function Lowcase
Lparameter cKey
If Len(cKey)<> 1 Then
	*Bao loi
	Thongbaoloi("Xin lçi, hµm nµy chØ dïng víi mét ký tù")	
	Return ""
Endif
cOldKey=cKey
cKey=Lower(cKey)
Do case
	Case cKey="¢"
		Return "©"		
	Case cKey="¡"
		Return "¨"
		 
	Case cKey="§"
		Return "®"
		
	Case cKey="£"
		Return "ª"
		
	Case cKey="¤"
		Return "«"	
	Case cKey="¥"
		Return "¬"
	
	Case cKey="¦"
		Return "­"
	Other 
		If  ASC(cKey)<=122 And ASC(cKey)>=97 Then
			Return cKey
		Else
			Return cOldKey
		Endif
		
Endcase

*****************************************************************************
************************************************************************
********	Function UPPERALL(cString)
********	cString: Xau ky tu 
********	Chuc nang: Tra lai mot xau hoa tu mot xau bat ky
************************************************************************
Function UPPERALL
Lparameter cString
PRIVATE cStr,cStrRet, i
cStr=ALLTRIM(cString)
cStrRet=""
FOR i=1 TO LEN(cStr)
	cStrRet=cStrRet+Upcase(SUBSTR(cStr,i,1))	
NEXT
RETURN cStrRet

*****************************************************************************
************************************************************************
********	Function LOWERALL(cString)
********	cString: Xau ky tu 
********	Chuc nang: Tra lai mot xau thuong tu mot xau bat ky
************************************************************************
Function LOWERALL
Lparameter cString
PRIVATE cStr,cStrRet, i
cStr=ALLTRIM(cString)
cStrRet=""
FOR i=1 TO LEN(cStr)
	cStrRet=cStrRet+Lowcase(SUBSTR(cStr,i,1))	
NEXT
RETURN cStrRet

************************************************************************
********	Function Findpath()
********	Chuc nang: Tim duong dan cho chuong trinh dang chay
************************************************************************
Function FindPath
Private posfirst,poslast,cpro
cpro= Sys(16)
*wait wind cpro
posfirst=At(":",cpro)
cpath=Right(cpro,len(cpro)-posfirst+2)
poslast=RAt("\",cpath)
Return Left(cpath,poslast-1)

************************************************************************
********	Function Findpath()
********	Chuc nang: Tim duong dan cho chuong trinh dang chay
************************************************************************
Function ChangeThreeColor
Lparameter nRed, nBlue,nGreen
Return (nGreen*256+nBlue)*256+nRed

************************************************************************
********	Procedure Set_On
********	Chuc nang: Tra lai moi truong lam viec
************************************************************************
Procedure Set_On
	_screen.TitleBar=1
	_Screen.Picture=""
	_Screen.Icon=""	
	Set Date American
	Set Century Off
	Set Talk On
	Set Status Off
	Close data All
	Set Safety Off
	Dele File Temp\*.*
	*Dele File *.dbf
	Set Safety On
	Set Console On
	Set Escape On
	Clear Event
	Set Safety On
	Set Sysmenu To Default		
RETURN

************************************************************************
********	Procedure Set_Off
********	Chuc nang: Dat moi truong lam viec
**********************************************************************
Procedure Set_Off
	Set Date French
	Set Century On
	Set Talk Off
	Set Status Off
	Set Safety Off
	Set Console Off
	Set Escape Off
	SET DELETED ON 
	Close Data All	
	Set Sysmenu Off	
	Set Console Off
Return
*************************************************************************************************************
*  PROCEDURE Exit_fox
*  Doi	:  None
*  Chuc nang: Ket thuc chuong trinh FOX
**************************************************************************************************************
PROCEDURE Exit_fox
Private lAnswer
 lAnswer	=0
lAnswer	=Traloi("B¹n cã thùc sù muèn tho¸t kh«ng", "CÈn thËn","\<Cã", "\<Kh«ng")
If lAnswer=6 Then
	Do Set_on		
Endif	
RETURN
**************************************************************************************************************
*  PROCEDURE ViewPicInScreen
*  Doi	: cObject, cPicture
*  Chuc nang: Them mot doi tuong anh cObject vao man hinh vo anh la  cPicture
**************************************************************************************************************
PROCEDURE ViewPicInScreen
Parameter cObject, cPicture
Private _cObject, _cPicture
_cObject=Allt(cObject)
_cPicture=Allt(cPicture)
_Screen.AddObject(_cObject,"Image")
_Screen.&_cObject..MOve(100,50)
_Screen.&_cObject..Picture=_cPicture
_Screen.&_cObject..Visible=.T.
RETURN
**************************************************************************************************************
*  PROCEDURE HidePicInScreen 
*  Doi	:cObject
*  Chuc nang: Huy doi tuong cObject tren man hinh
**************************************************************************************************************
PROCEDURE HidePicInScreen
Parameter cObject
Private _cObject
_cObject=Allt(cObject)
_Screen.RemoveObject(_cObject)
RETURN
**************************************************************************************************************
*  Ham	: FUNCTION RenumberDelete 
*  Doi	: cFile
*  Chuc nang: Danh lai STT cho cac file bi danh dau xoa co truong STT
**************************************************************************************************************
FUNCTION RenumberDelete 
Lparameter cFile
Private nSTT
Sele(cFile)
nSTT=1
Go top
Do While !Eof()
	If !Delete()
		Replace STT With nSTT
		nSTT=nSTT+1
	Endif
	Skip	
Enddo
**************************************************************************************************************
*  Ham	: FUNCTION NoneChild 
*  Doi	:  cParentFile, cChildFile, cParentField, cChildField, cFieldJoint, cFileReturn
*  Chuc nang: Tra lai du lieu o tep cFileReturn voi noi dung cParentFile khong co o tep cChildFile tai truong cField
**************************************************************************************************************
FUNCTION NoneChild 
Parameter cParentFile, cChildFile, cParentField, cChildField, cFieldJoint, cFieldJointJoint, cFileReturn
Private lInc
cValues="&cParentFile..&cParentField, &cParentFile..Ten"
cField="Ma C(5),Ten C(25)"
If !Empty(cFieldJointJoint)
	cField=cField+", &cFieldJointJoint C(5)"
	cValues=cValues+", &cParentFile..&cFieldJointJoint"
Endif
Sele 0
Create Cursor &cFileReturn(&cField)

=VerifyFile(cParentFile)
Sele(cParentFile)
Set Order To 1
Go top
=VerifyFile(cChildFile)
Sele(cChildFile)
Set Order To 2 && Chi so theo ma cha
Go top

Do while !Eof(cChildFile)
	lInc=.F.
	Sele(cChildFile) 
	Do While Allt(&cChildFile..&cFieldJoint)==Allt(&cParentFile..&cParentField)
		lInc=.T.
		Skip
	Enddo		
	Sele(cParentFile)
	If !lInc
		Insert Into (cFileReturn) Values(&cValues )
	Endif
	Skip	
Enddo
* Con lai la o tep cha nhung ma khong con

Sele(cParentFile)
Do while !Eof()
	Insert Into (cFileReturn) Values(&cValues )
	Skip
Enddo
Sele(cChildFile)
Set Order To 1 && Chi so lai theo Ma 
If Reccount(cFileReturn)>0
	Return .T.
Endif
Return .F.	


**************************************************************************************************************
*  PROCEDURE FindBP
*  Chuc nang: Tim tat ca ca bo phan da nhap (Tenchinh, TenPhu1, Tenphu2)
**************************************************************************************************************
PROCEDURE FindBP
* Chia lam 3 phan (Ongkhongcha + Ongcha + Ongchachau)
=NoneChild("Bpchinh", "Bpphu1","Ma", "Ma", "Machinh","", "tmpBPchinh")
=NoneChild("Bpphu1", "Bpphu2","Ma", "Ma", "Maphu1","Machinh", "tmpBPphu")

Sele 0
Sele Bpchinh.Ma AS Machinh , tmpBPphu.Ma,Allt(Bpchinh.Ten)+", "+Allt(tmpBPphu.Ten) AS Ten;
	From tmpBPphu, Bpchinh ;
	Where Allt(tmpBPphu.Machinh)==Allt(Bpchinh.Ma);
	Into Cursor tmpBPphu1

Sele 0
Sele Bpchinh.Ma AS Machinh , Bpphu2.Ma,Allt(Bpchinh.Ten)+", "+Allt(Bpphu1.Ten)+", "+Allt(Bpphu2.Ten) AS Ten;
	From Bpphu2, Bpphu1, Bpchinh ;
	Where Allt(Bpphu2.Maphu1)==Allt(Bpphu1.Ma) AND Allt(Bpphu1.Machinh)==Allt(Bpchinh.Ma);
	Into Table Temp\tmpBPphu2
	 
Sele tmpBPphu1 
Scan
	Insert Into tmpBPphu2(Machinh, Ma, Ten) Values(tmpBPphu1.Machinh, tmpBPphu1.Ma,tmpBPphu1.Ten)
Endscan
Sele tmpBPchinh
Scan
	Insert Into tmpBPphu2(Machinh, Ma, Ten) Values(tmpBPchinh.Ma, tmpBPchinh.Ma,tmpBPchinh.Ten)
Endscan

**************************************************************************************************************
*  Ham	: FUNCTION CalculateDate 
*  Doi	:dDate, nMonth : Ngay nhap vao, so thang can cong
*  Chuc nang: Tra lai ngay duoc cong tu mot ngay truoc voi so thang can cong
**************************************************************************************************************
FUNCTION CalculateDate 
Lparameter dDate, nMonth
Private nDay1, nMonth1, nYear1, TotalMonth, cDate
nDay1=Day(dDate)
nMonth1=Month(dDate)
nYear1=Year(dDate)

TotalMonth=nMonth+nMonth1
If TotalMonth>12 Then
	nYear1=nYear1+Int(TotalMonth/12)
	nMonth1=nMonth1+Mod(TotalMonth,12)
Else	
	nMonth1=TotalMonth
Endif

cDate=Iif(nDay1>9, Ltrim(str(nDay1)), "0"+Ltrim(str(nDay1)) )+"/"+Iif(nMonth1>9, Ltrim(str(nMonth1)), "0"+Ltrim(str(nMonth1)) )+"/"+;
	Ltrim(Str(nYear1))
Return CtoD(cDate)

**************************************************************************************************************
*  Ham		: Datetime_To_Date 
*  Doi		: dDateTime - du lieu kieu DateTime
*  Chuc nang: Doi kieu du lieu dang datetime sang dang date
**************************************************************************************************************
FUNCTION Datetime_To_Date 
Lparameter dDateTime
If Year(dDateTime)=1900 && Rong
	Return {}
Else
	Return Date(Year(dDateTime),Month(dDateTime),Day(dDateTime))
ENDIF

**************************************************************************************************************
*  Ham		: VND
*  Doi		: Num - So can danh van ra chu
*  Chuc nang: Danh van so sang chu
**************************************************************************************************************

Function VND
Parameter Num
#Define TY "tû"
#Define TRIEU "triÖu"
#Define NGHIN "ngh×n"
#Define TRAM "tr¨m"
#Define CHUC "m­¬i"
#Define DONG " ®ång"
#Define CACH " "

Spell_Num=""
* Neu la so am thi doi dau
If Num=0 then
	Return ""
Endif

DAU=""
if  Num<0 then	
	Num=-Num
	DAU="©m "
Endif

* Tach ra 3 so 1 
* Neu lon hon 1 ty
lBegin=.F.
If Num/1000000000 >=1 Then
	T_digit=Int(Num/1000000000)
	Num=Mod(Num,1000000000)
	lBegin=!lBegin
	Spell_Num=Spell_Num+Three_Digit(T_digit,TY,lBegin)	
	lBegin=.T.
Endif

* Neu lon hon 1 trieu
If Num/1000000 >=1 Then
	T_digit=Int(Num/1000000)
	Num=Mod(Num,1000000)
	lBegin=!lBegin
	Spell_Num=Spell_Num+Three_Digit(T_digit,TRIEU,lBegin)
	lBegin=.T.
Endif
* Neu lon hon 1 nghin
If Num/1000 >=1 Then
	T_digit=Int(Num/1000)
	Num=Mod(Num,1000)
	lBegin=!lBegin
	Spell_Num=Spell_Num+Three_Digit(T_digit,NGHIN,lBegin)
	lBegin=.T.
Endif
lBegin=!lBegin
* Con lai la hang tram tro di
Spell_Num=LTRIM(DAU+Spell_Num+Three_Digit(num, "",lBegin))
Spell_Num=Upcase(LEFT(Spell_Num,1))+RTRIM(RIGHT(Spell_Num, LEN(Spell_Num)-1))+ DONG
Return Spell_Num

**************************************************************************************************************
*  Ham		: Three_Digit
*  Doi		: T_Num, cDonvitinh, lBegin - Tung cum 3 chu so tu nhien (XXX) , Don vi tinh (TY, TRAM, TRIEU, DONG), Vi tri bat dau danh van
*  Chuc nang: danh van tung cum ba chu so tu nhien ra chu (VD: 102 --> mét tr¨m linh hai )
**************************************************************************************************************
Function Three_Digit
Parameter T_Num, cDonvitinh, lBegin
Private cRet, Hangtram, Hangchuc, Donvi
IF T_Num=0 Then
	Return ""
ENDIF

cRet=""
Hangtram=Int(T_num/100)
Hangchuc=Int(Mod(T_num,100)/10)
Donvi=Mod(T_num,10)

* Xu ly
If   Hangtram>0 OR Hangtram=0 AND !lBegin
	cRet=cRet+One_Digit(Hangtram)+CACH+TRAM+CACH
Endif

If Hangchuc>1
	cRet=cRet+One_Digit(Hangchuc)+CACH+CHUC+CACH
ELSE
	IF Hangchuc= 1 then
		cRet=cRet+"m­êi"+CACH	
	Endif
Endif

If Donvi>0
	IF Hangchuc=0  AND  HangTram>=0 THen
		cRet=cRet+IIF(!lBegin, "linh","")+CACH+One_Digit(Donvi)+CACH			
	ELSE
		IF Donvi<>1 OR Donvi=1 && AND EMPTY(cDonvitinh)
			cRet=cRet+One_Digit(Donvi)+CACH
		ELSE
			IF HangChuc>0 AND !EMPTY(cDonvitinh) && hang don vi
				cRet=cRet+"mèt"+CACH		
			ENDIF 
		ENDIF 
	Endif
ENDIF

Return cRet+cDonvitinh+CACH

**************************************************************************************************************
*  Ham		: One_Digit
*  Doi		: O_Num - So tu nhien can danh van ra chu
*  Chuc nang: danh van tung so tu nhien ra chu
**************************************************************************************************************
Function One_Digit
Parameter O_Num && Cac so tu 0..9
Dimension aNumber(10)
aNumber(1)="kh«ng"
aNumber(2)="mét"
aNumber(3)="hai"
aNumber(4)="ba"
aNumber(5)="bèn"
aNumber(6)="n¨m"
aNumber(7)="s¸u"
aNumber(8)="b¶y"
aNumber(9)="t¸m"
aNumber(10)="chÝn"
Return aNumber(O_Num+1)

**************************************************************************************************************
*  Ham		: Calc_Owing
*  Doi		: cHouse_ID
*  Chuc nang: Tra lai tong so tien no cua ho co so nha la House_ID tu tep tmpKhachNO voi truong Owing
**************************************************************************************************************
FUNCTION Calc_Owing
PARAMETERS cHouse_ID
PRIVATE SaveAlias, nRet
SaveAlias=ALIAS()
SELECT tmpKhachNO 
LOCATE FOR House_ID=cHouse_ID
nRet=Owing
IF !EMPTY(SaveAlias)
	SELECT(SaveAlias)
Endif
RETURN nRet

**************************************************************************************************************
*  Ham		: CheckOverDate
*  Doi		: nMonth, nYear - Thang, nam kiem tra
*  Chuc nang: Tra lai gia tri dung neu thang nam lon hon moc thoi gian
**************************************************************************************************************
FUNCTION CheckOverDate
LPARAMETERS nMonth, nYear
SELECT TimeMark
IF nyear>Year OR nYear=Year AND nMonth=>Month Then
	RETURN .T.
ENDIF 
RETURN .F.

*************************************************************
*  Ham		: Space_Cutting
*  Doi		: cString : Xau ky tu can cat 
*  Chuc nang: Cat nhung ky tu trang trong xau
**************************************************************************************************************
FUNCTION Space_Cutting
Parameter cString
  Do while " " $ cString
	cString=Strt(cString," ","")
  enddo
Return cString  

*************************************************************
*  Ham		: CounterAround
*  Doi		: nValue, nLowValue, nHighValue: Gia tri hien tai, gia tri thap hon thap nhat, gia tri cao hon cao nhat
*  Chuc nang: Bo dem quay tron
**************************************************************************************************************
FUNCTION CounterAround
Parameter  nValue, nLowValue, nHighValue
IF nValue=nLowValue Then
	RETURN nHighValue-1
ELSE
	IF nValue=nHighValue Then
		RETURN nLowValue+1
	ENDIF 
ENDIF 
RETURN nValue

 *************************************************************
*  Ham		: Check_Inputted
*  Doi		: Value, FieldName, FileName: Gia tri can tim ( Kieu ky tu ) , Ten truong, Ten tep
*  Chuc nang: Kiem tra xem truong FieldName trong tep aliasname co gia tri bang Value khong
*  Co tra ve true, nguoc lai false 
**************************************************************************************************************
FUNCTION Check_Inputted
Parameter  Value, FieldName, FileName, cAddcon
PRIVATE lset_exact, SaveAlias, nHandle
nHandle=_Screen.handle
SaveAlias=ALIAS()
SELECT 0
cSQL=" SELECT * From "+FileName+ " Where "+FieldName+"='"+Value+"'" +;
	IIF(EMPTY(cAddcon),""," And "+cAddcon)
=SQLCommand(cSQL, "tmpResult")
lRet=IIF(RECCOUNT("tmpResult")=0,.F.,.T.)

IF NOT EMPTY(SaveAlias)
	SELECT(SaveAlias)
Endif
RETURN lRet

**************************************************************************************************************
*  Ham		: AddTagContract 
*  Doi		: cNumber_ID 
*  Chuc nang: Tra lai duoi hop dong theo quan
**************************************************************************************************************
FUNCTION  AddTagContract 
LPARAMETERS cNumber_ID
PRIVATE cDist_ID
cDist_ID=LEFT(ALLTRIM(cNumber_ID),2)
DO case
	CASE cDist_ID='01'
		RETURN 'HB'
	CASE cDist_ID='02'
		RETURN 'LC'
	CASE cDist_ID='03'
		RETURN 'NQ'
	CASE cDist_ID='04'	
		RETURN 'KA'
ENDCASE

**************************************************************************************************************
*  Ham		: Percentage
*  Doi		: nFloor, kind_office_id : Tang thu, Ma khoi co quan
*  Chuc nang: Tra lai ty le tien theo gia goc cho tang thu n 
**************************************************************************************************************
FUNCTION  Percentage
PARAMETERS nFloor, kind_office_id && (90%)^(nnFloor -1)
PRIVATE nKind
DO case
	CASE kind_office_id=1 OR kind_office_id=2
		nKind=1
	CASE kind_office_id=5 OR kind_office_id=6 OR kind_office_id=9
		nKind=2
	CASE kind_office_id=3 OR kind_office_id=4 OR kind_office_id=7 OR kind_office_id=8
		nKind=3	
	OTHERWISE
		nKind=1
ENDCASE 

RETURN IIF(nKind=1,1,0.9^(nFloor-1))

**************************************************************************************************************
*  Ham		: Correct_RentCost 
*  Doi		: Khong co
*  Chuc nang: Hieu chinh lai tien thue nha cho khoi co quan
**************************************************************************************************************
PROCEDURE Correct_RentCost && Hieu chinh lai tien thue nha cho khoi co quan 
PRIVATE nHandle, cSQL
nHandle=_screen.handle

SELECT 0
cSQL=" SELECT Contract,"+;
	" sum((main_area+(corr_Area+balc_area+parc_area)/2)*Value+add_area*1000) as Rent_Cost"+;
	" FROM houseoff "+;
	" group BY Contract " 
	
=SQLCommand( cSQL,"Monney")

SELECT Monney
GO TOP 
SCAN
	cSQL="Update Office set rent_cost="+LTRIM(STR(ROUND(Rent_Cost,-2)))+;
		" Where Contract='"+Contract+"'"
	
	=SQLCommand(cSQL)
ENDSCAN 
* Tim nhung hop dong khong co chi tiet gan Tien thue nha =0
cSQL=" Update Office "+;
	" Set rent_cost=0 "+;
	" Where Contract not in "+;
	" ( Select Contract From houseoff) "
=SQLCommand( cSQL)

RETURN 

**************************************************************************************************************
*  Thu tuc	: SQLCommand 
*  Doi		: nHandle, cSQLCommand, cCursor && The lenh, Cau lenh SQL, [ Dua ra Query]
*  Chuc nang: Thuc hien cau lenh SQL
**************************************************************************************************************
PROCEDURE SQLCommand && Thuc hien cau lenh SQL
LPARAMETERS cSQLCommand, cCursor && The lenh, Cau lenh SQL, [ Dua ra Query]
PRIVATE cCur, nHandle
IF EMPTY(cCursor)
	cCur=""
ELSE
	cCur=",'"+ ALLTRIM(cCursor)+"'"
	IF USED(cCursor) && Neu file dang mo thi dong lai
		USE IN (cCursor)
	ENDIF
ENDIF  
=SQLtoText(cSQLCommand)
nHandle=SQLCONNECT("qlnha", "sa", _Screen.Password)
*= SQLSETPROP(nHandle, 'asynchronous', .F.) 
*=SQLSETPROP(nHandle, "PacketSize", 4096 )
*= SQLSETPROP(nHandle, 'Transactions', 1) && Auto transactions
= SQLSETPROP(nHandle, 'Transactions', 2)  && Manual transactions
= SQLEXEC(nHandle, cSQLCommand &cCur ) 
= SQLCOMMIT(nHandle) 
=SQLDISCONNECT(nHandle) 
RETURN 

**************************************************************************************************************
*  Ham	: CreateConnection
*  Chuc nang: Tra lai gia tri >0 neu ket noi duoc voi du lieu, -2 nguoc lai
**************************************************************************************************************
FUNCTION CreateConnection
LPARAMETERS cDatabase, cLoginName, cPassword
RETURN  SQLCONNECT(ALLTRIM(cDatabase), ALLTRIM(cLoginName), ALLTRIM(cPassword))	

**************************************************************************************************************
*  Ham	: SeparateName
*  Doi: cString la xau chua ho ten
*  Chuc nang: Tach ten trong xau ho ten, phuc vu sap xep
**************************************************************************************************************
FUNCTION SeparateName
LPARAMETERS cString
cString=ALLTRIM(cString)
RETURN  RIGHT(cString,LEN(cString)-RAT(" ",cString))

**************************************************************************************************************
*  Ham	: SeparateLast
*  Doi: cString la xau chua ho ten
*  Chuc nang: Tach ho dem trong xau ho ten, phuc vu sap xep
**************************************************************************************************************
FUNCTION SeparateLast
LPARAMETERS cString
cString=ALLTRIM(cString)
RETURN  Left(cString,LEN(cString)-RAT(" ",cString)-1)
**************************************************************************************************************
*  Ham	: TotalReceipt
*  Doi: cAlias Ten bang de tinh tong BL
*  Chuc nang: Tinh tong BL theo 1 bang
**************************************************************************************************************
FUNCTION TotalReceipt
LPARAMETERS cAlias
PRIVATE nTotal
nTotal=0
SELECT(caLias)
SCAN
	nTotal=nTotal+OT_Give_Rept(FromReceip, ToReceipt)
ENDSCAN 
RETURN nTotal
**************************************************************************************************************
*  Ham	: OT_Give_Rept
*  Doi: FromReceipt, ToReceipt Tu BL den BL
*  Chuc nang: Tinh tong bien lai trong 1 khoang BL
**************************************************************************************************************
FUNCTION OT_Give_Rept
LPARAMETERS nFromReceipt, nToReceipt
PRIVATE nFrom, nTo
nFrom=VAL(RIGHT(nFromReceipt,7))
nTo=VAL(RIGHT(nToReceipt,7))
RETURN nTo-nFrom+1

**************************************************************************************************************
* Thu tuc:  SQLtoText
* Doi: cSQL : Cau lenh SQL
* Chuc nang: Tao file "C:\sql.txt" chua cau lenh SQL
**************************************************************************************************************
PROCEDURE SQLtoText
PARAMETERS cSQL
PRIVATE nHandle
nHandle=FCREATE("C:\sql.txt")
=FWRITE(nHandle,cSQL)
=FCLOSE(nHandle)
RETURN 

**************************************************************************************************
* PROCEDURE SetSystemFont
* Doi  PFONTNAME, PBUFF : Ten Font & Bo dem
* Muc dich: Dat Font cho Menu va Title Windows, muc dich de hien thi Font tieng viet trong WinXP
* Su dung:  =SetSystemFont("Ms Sans Serif")
**************************************************************************************************
PROCEDURE SetSystemFont
PARAMETER PFONTNAME, PBUFF
PFONTNAME = IIF(EMPTY(PFONTNAME), "Arial", PFONTNAME)
PRIVATE ALL
IF LEN(PFONTNAME)>31
RETURN
ENDIF
SPI_GETNONCLIENTMETRICS = 41
SPI_SETNONCLIENTMETRICS = 42
LCBUFFSIZE = 1
LCCAPTION = 053
LCSMCAPTION = 121
LCMENU = (0189)
LCSTATUS = (0249)
LCMESSAGE = 0309
LCBUFF = CHR(MOD(340, 256))+CHR(INT((1.328125)))+CHR(0)+CHR(0)+REPLICATE(CHR(0), 0336)
DECLARE INTEGER SystemParametersInfo IN USER32 AS SYSINFO INTEGER, INTEGER, STRING @, INTEGER
SYSINFO(SPI_GETNONCLIENTMETRICS,LEN(LCBUFF),@LCBUFF,0)
OLDFONT = LCBUFF
LCBUFF = STUFF(LCBUFF, LCCAPTION, LEN(PFONTNAME)+1, PFONTNAME+CHR(0))
LCBUFF = STUFF(LCBUFF, LCSMCAPTION, LEN(PFONTNAME)+1, PFONTNAME+CHR(0))
LCBUFF = STUFF(LCBUFF, LCMENU, LEN(PFONTNAME)+1, PFONTNAME+CHR(0))
LCBUFF = STUFF(LCBUFF, LCSTATUS, LEN(PFONTNAME)+1, PFONTNAME+CHR(0))
LCBUFF = STUFF(LCBUFF, LCMESSAGE, LEN(PFONTNAME)+1, PFONTNAME+CHR(0))

IF OLDFONT<>LCBUFF
LCFILENAME = SYS(2023)+"\ADFont.log"
STRTOFILE(OLDFONT, LCFILENAME)
ENDIF
LCFILENAME = SYS(2023)+"\ADFont.log"
STRTOFILE(OLDFONT, LCFILENAME)
IF EMPTY(PBUFF)
SYSINFO(SPI_SETNONCLIENTMETRICS,LEN(LCBUFF),@LCBUFF,0)
ELSE
SYSINFO(SPI_SETNONCLIENTMETRICS,LEN(PBUFF),@PBUFF,0)
ENDIF
CLEAR DLLS ALIAS "SystemParametersInfo"
RETURN
ENDPROC
**************************************************************************************************
* FUNCTION ABC2VNI
* Doi  PFONTNAME, PBUFF : Ten Font & Bo dem
* Muc dich: Dat Font cho Menu va Title Windows, muc dich de hien thi Font tieng viet trong WinXP
* Su dung:  =SetSystemFont("Ms Sans Serif")
**************************************************************************************************
FUNCTION ABC2VNI
PARAMETERS cString
PRIVATE n, i
n=55
DIMENSION  aABC(n), aVNI(n)
* Khai bao mang ABC
 aABC(1)="¡"
 aABC(2)="¨"
 aABC(3)="»"
 aABC(4)="¾"
 aABC(5)="½"
 aABC(6)="¼"
 aABC(7)="Æ"
 
 aABC(8)="¢"
 aABC(9)="©"
 aABC(10)="Ç"
 aABC(11)="Ê"
 aABC(12)="É"
 aABC(13)="È"
 aABC(14)="Ë"
 
 aABC(15)="Ð"
 aABC(16)="Ì"
 aABC(17)="Ï"
 aABC(18)="Î"
 aABC(19)="Ñ"
 
 aABC(20)="£"
 aABC(21)="ª"
 aABC(22)="Õ"
 aABC(23)="Ò"
 aABC(24)="Ô"
 aABC(25)="Ó"
 aABC(26)="Ö"
 
 aABC(27)="Ý"
 aABC(28)="×"
 aABC(29)="Ü"
 aABC(30)="Ø"
 aABC(31)="Þ"
 
 aABC(32)="ã"
 aABC(33)="ß"
 aABC(34)="â"
 aABC(35)="á"
 aABC(36)="ä"
 
 aABC(37)="¤"
 aABC(38)="«"
 aABC(39)="è"
 aABC(40)="å"
 aABC(41)="ç"
 aABC(42)="æ"
 aABC(43)="é" 
 
 aABC(37)="¥"
 aABC(38)="¬"
 aABC(39)="í"
 aABC(40)="ê"
 aABC(41)="ì"
 aABC(42)="ë"
 aABC(43)="î" 
 
 aABC(44)="ó"
 aABC(45)="ï"
 aABC(46)="ò"
 aABC(47)="ñ"
 aABC(48)="ô"
 
 aABC(49)="¦"
 aABC(50)="­"
 aABC(51)="ø"
 aABC(52)="õ"
 aABC(53)="÷" 
 aABC(54)="ö"
 aABC(55)="ù"
 
* Khai bao mang VNI 
aVNI(1)="AÊ"
aVNI(2)="aê"
aVNI(3)="aè"
aVNI(4)="aé"
aVNI(5)="aü"
aVNI(6)="aú"
aVNI(7)="aë"

aVNI(8)="AÂ"
aVNI(9)="aâ"
aVNI(10)="aà"
aVNI(11)="aá"
aVNI(12)="aã"
aVNI(13)="aå"
aVNI(14)="aä"

aVNI(15)="eù"
aVNI(16)="eø"
aVNI(17)="eõ"
aVNI(18)="eû"
aVNI(19)="eï"

aVNI(20)="EÂ"
aVNI(21)="eâ"
aVNI(22)="eá"
aVNI(23)="eà"
aVNI(24)="eã"
aVNI(25)="eå"
aVNI(26)="eä"

aVNI(27)="í"
aVNI(28)="ì"
aVNI(29)="ó"
aVNI(30)="æ"
aVNI(31)="ò"

aVNI(32)="où"
aVNI(33)="oø"
aVNI(34)="oõ"
aVNI(35)="oû"
aVNI(36)="oï"

aVNI(37)="OÂ"
aVNI(38)="oâ"
aVNI(39)="oá"
aVNI(40)="oà"
aVNI(41)="oã"
aVNI(42)="oå"
aVNI(43)="oä"

aVNI(37)="Ô"
aVNI(38)="ô"
aVNI(39)="ôù"
aVNI(40)="ôø"
aVNI(41)="ôõ"
aVNI(42)="ôû"
aVNI(43)="ôï"

aVNI(44)="uù"
aVNI(45)="uø"
aVNI(46)="uõ"
aVNI(47)="uû"
aVNI(48)="uï"

aVNI(49)="Ö"
aVNI(50)="ö"
aVNI(51)="öù"
aVNI(52)="öø"
aVNI(53)="öõ"
aVNI(54)="öû"
aVNI(55)="öï"

cString=ALLTRIM(cString)
FOR i=1 TO n	
	cString=STRTRAN(cString, aABC(i), aVNI(i)) 
NEXT 
RETURN cString
 
*************************************************************************
FUNCTION Add_Dot
PARAMETERS cNumber, cDot
PRIVATE cLeft, cRight, cReturn_String
* 1.000.000
cNumber=ALLTRIM(cNumber)
cReturn_String=""
IF LEN(cNumber)<=3
	RETURN cNumber
ENDIF 
cLeft=Left(cNumber, LEN(cNumber)-3)
cReturn_String=cDot+Right(cNumber,3)
cNumber=cLeft

DO WHILE LEN(cNumber)>3
	cLeft=Left(cNumber, LEN(cNumber)-3)
	cReturn_String=cDot+Right(cNumber,3)+cReturn_String
	cNumber=cLeft
ENDDO 
cReturn_String=cNumber+cReturn_String
RETURN cReturn_String