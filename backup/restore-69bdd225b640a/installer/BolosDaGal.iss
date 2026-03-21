[Setup]
AppId={{F5E89AA4-2EB0-4A18-8D02-4A09BE376A11}
AppName=Bolos da Gal
AppVersion=1.0.0
AppPublisher=Bolos da Gal
DefaultDirName=C:\BolosDaGal
DefaultGroupName=Bolos da Gal
DisableProgramGroupPage=yes
WizardStyle=modern
Compression=lzma
SolidCompression=yes
OutputDir=.
OutputBaseFilename=Instalador-BolosDaGal
SetupIconFile=..\bolos.ico
UninstallDisplayIcon={app}\bolos.ico
PrivilegesRequired=admin

[Languages]
Name: "portuguese"; MessagesFile: "compiler:Languages\BrazilianPortuguese.isl"

[Tasks]
Name: "desktopicon"; Description: "Criar atalho na area de trabalho"; GroupDescription: "Atalhos:"

[Files]
Source: "..\*.php"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\*.sql"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\*.md"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\*.bat"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\*.png"; DestDir: "{app}"; Flags: ignoreversion
Source: "..\*.ico"; DestDir: "{app}"; Flags: ignoreversion
Source: "LEIA-ME-INSTALADOR.txt"; DestDir: "{app}"; Flags: ignoreversion

[Dirs]
Name: "{app}\backup"

[Icons]
Name: "{group}\Bolos da Gal"; Filename: "{app}\iniciar-sistema.bat"; WorkingDir: "{app}"; IconFilename: "{app}\bolos.ico"
Name: "{commondesktop}\Bolos da Gal"; Filename: "{app}\iniciar-sistema.bat"; WorkingDir: "{app}"; IconFilename: "{app}\bolos.ico"; Tasks: desktopicon

[Run]
Filename: "{sys}\notepad.exe"; Parameters: """{app}\\LEIA-ME-INSTALADOR.txt"""; Description: "Abrir guia rapido de instalacao"; Flags: postinstall skipifsilent

[Code]
var
  ConfigPage: TWizardPage;
  PHPPathEdit: TEdit;
  MySQLPathEdit: TEdit;
  DBHostEdit: TEdit;
  DBNameEdit: TEdit;
  DBUserEdit: TEdit;
  DBPassEdit: TEdit;
  ImportDatabaseCheck: TNewCheckBox;

procedure AddEdit(APage: TWizardPage; const CaptionText: string; var EditControl: TEdit; TopPos: Integer; const DefaultValue: string; Password: Boolean);
var
  LabelControl: TLabel;
begin
  LabelControl := TLabel.Create(APage);
  LabelControl.Parent := APage.Surface;
  LabelControl.Caption := CaptionText;
  LabelControl.Left := 0;
  LabelControl.Top := TopPos;

  EditControl := TEdit.Create(APage);

  EditControl.Parent := APage.Surface;
  EditControl.Left := 0;
  EditControl.Top := TopPos + 18;
  EditControl.Width := APage.SurfaceWidth;
  EditControl.Text := DefaultValue;

  if Password then
    EditControl.PasswordChar := '*';
end;

function EscapePHPString(const Value: string): string;
begin
  Result := Value;
  StringChangeEx(Result, '\\', '\\\\', True);
  StringChangeEx(Result, '''', '\''', True);
end;
procedure InitializeWizard;
begin
  ConfigPage := CreateCustomPage(wpSelectDir, 'Configuracao do Ambiente', 'Informe PHP, MySQL e banco.');
  AddEdit(ConfigPage, 'Caminho do php.exe', PHPPathEdit, 0, 'C:\php\php.exe', False);
  AddEdit(ConfigPage, 'Caminho do mysql.exe (opcional)', MySQLPathEdit, 60, 'C:\Program Files\MySQL\MySQL Server 8.0\bin\mysql.exe', False);
  AddEdit(ConfigPage, 'Host do MySQL', DBHostEdit, 120, '127.0.0.1', False);
  AddEdit(ConfigPage, 'Nome do banco', DBNameEdit, 180, 'bolosdagal', False);
  AddEdit(ConfigPage, 'Usuario do MySQL', DBUserEdit, 240, 'root', False);
  AddEdit(ConfigPage, 'Senha do MySQL', DBPassEdit, 300, '', True);

  ImportDatabaseCheck := TNewCheckBox.Create(ConfigPage);
  ImportDatabaseCheck.Parent := ConfigPage.Surface;
  ImportDatabaseCheck.Left := 0;
  ImportDatabaseCheck.Top := 360;
  ImportDatabaseCheck.Width := ConfigPage.SurfaceWidth;
  ImportDatabaseCheck.Caption := 'Importar o arquivo database.sql ao final da instalacao';
  ImportDatabaseCheck.Checked := True;
end;

function NextButtonClick(CurPageID: Integer): Boolean;
begin
  Result := True;

  if CurPageID = ConfigPage.ID then
  begin
    if not FileExists(PHPPathEdit.Text) then
    begin
      MsgBox('Nao foi encontrado o php.exe no caminho informado.', mbError, MB_OK);
      Result := False;
      exit;
    end;

    if ImportDatabaseCheck.Checked and (Trim(MySQLPathEdit.Text) <> '') and (not FileExists(MySQLPathEdit.Text)) then
    begin
      MsgBox('O mysql.exe nao foi encontrado. Corrija o caminho ou desmarque a importacao automatica.', mbError, MB_OK);
      Result := False;
      exit;
    end;
  end;
end;

procedure WriteConfigFile;
var
  Content: string;
begin
  Content :=
    '<?php' + #13#10 + #13#10 +
    'return [' + #13#10 +
    '    ''db_host'' => ''' + EscapePHPString(DBHostEdit.Text) + ''',' + #13#10 +
    '    ''db_name'' => ''' + EscapePHPString(DBNameEdit.Text) + ''',' + #13#10 +
    '    ''db_user'' => ''' + EscapePHPString(DBUserEdit.Text) + ''',' + #13#10 +
    '    ''db_pass'' => ''' + EscapePHPString(DBPassEdit.Text) + ''',' + #13#10 +
    '];' + #13#10;

  SaveStringToFile(ExpandConstant('{app}\config.php'), Content, False);
end;

procedure WriteStartScript;
var
  Content: string;
begin
  Content :=
    '@echo off' + #13#10 +
    'setlocal' + #13#10 + #13#10 +
    'set "PROJECT_DIR=' + ExpandConstant('{app}') + '"' + #13#10 +
    'set "PHP_EXE=' + PHPPathEdit.Text + '"' + #13#10 + #13#10 +
    'cd /d "%PROJECT_DIR%"' + #13#10 + #13#10 +
    'start "" http://localhost:8000' + #13#10 +
    '"%PHP_EXE%" -S localhost:8000' + #13#10;

  SaveStringToFile(ExpandConstant('{app}\iniciar-sistema.bat'), Content, False);
end;

function BuildMySQLCommand: string;
var
  PasswordPart: string;
begin
  if DBPassEdit.Text <> '' then
    PasswordPart := ' -p"' + DBPassEdit.Text + '"'
  else
    PasswordPart := '';

  Result :=
    '/C ""' + MySQLPathEdit.Text + '" -h "' + DBHostEdit.Text + '" -u "' + DBUserEdit.Text + '"' +
    PasswordPart + ' < "' + ExpandConstant('{app}\database.sql') + '""';
end;

procedure ImportDatabase;
var
  ResultCode: Integer;
begin
  if not ImportDatabaseCheck.Checked then
    exit;

  if Trim(MySQLPathEdit.Text) = '' then
  begin
    MsgBox('Instalacao concluida. Execute o database.sql manualmente no MySQL Workbench.', mbInformation, MB_OK);
    exit;
  end;

  if not Exec(ExpandConstant('{cmd}'), BuildMySQLCommand, '', SW_HIDE, ewWaitUntilTerminated, ResultCode) or (ResultCode <> 0) then
    MsgBox('Instalacao concluida, mas nao foi possivel importar o banco automaticamente.'#13#10 +
      'Execute o arquivo database.sql manualmente no MySQL Workbench.', mbInformation, MB_OK);
end;

procedure CurStepChanged(CurStep: TSetupStep);
begin
  if CurStep = ssPostInstall then
  begin
    WriteConfigFile;
    WriteStartScript;
    ImportDatabase;
  end;
end;



