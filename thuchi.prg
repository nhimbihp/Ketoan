* Beginning processing program
Private  cPath,  cDir
cPath=FindPath()
CD(cpath)
cDir="Temp"

* Creating temporary directory
If !Directory(cDir)
	MD(cDir)
Endif
*Dat Thu tuc va thu vien 
Set Procedure to Library Additive

*Tao ra danh sach File
_screen.windowstate=2
_screen.TitleBar=1
*_screen.Icon="qlnha.ico"
Do Set_Off
=SetSystemFont(".VnArial")
DO FORM frmThuChi

************************************************************************
Function FindPath
Private posfirst,poslast,cpro
cpro= Sys(16)
posfirst=At(":",cpro)
cpath=Right(cpro,len(cpro)-posfirst+2)
poslast=RAt("\",cpath)
Return Left(cpath,poslast-1)

