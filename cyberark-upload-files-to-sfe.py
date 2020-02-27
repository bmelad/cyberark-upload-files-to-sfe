import requests, json, base64, os
from requests.packages.urllib3.exceptions import InsecureRequestWarning
requests.packages.urllib3.disable_warnings(InsecureRequestWarning)

verifySSL = False
headers = {}

def login(user, passwd):
    global session_id, headers
    res = requests.post(base_url + 'WebServices/auth/Cyberark/CyberArkAuthenticationService.svc/Logon', json = { "username": user, "password": passwd }, verify = verifySSL)
    if (res.status_code == 200):
        session_id = (res.json())['CyberArkLogonResult']
        headers = {'Authorization': session_id}
        return True
    else:
        return False
        
def logout():
    res = requests.post(base_url + 'WebServices/auth/Cyberark/CyberArkAuthenticationService.svc/Logoff', headers = headers, verify = verifySSL)
    if (res.status_code == 200):
        return True
    else:
        return False

def upload_file(safe_name, file_path, folder_name):
    file_name = file_path.split('\\')[len(file_path.split('\\'))-1]
    with open(file_path, 'rb') as f:
        res = requests.post(base_url + 'WebServices/API.svc/Upload/Safes/' + safe_name + '/Files/' + file_name + '?folder=' + folder_name, headers = headers, verify = verifySSL, data=f)
    return (res.status_code)

session_id = ''
username = '{username}'
password = '{password}'
host = '{cyberark-sfe-address}'
base_url = 'https://' + host + '/SFE/'
safe_name = '{safe-name}'
folder_path = '/tmp/files/'

if login(username, password):
    print('logged-in successfully.')
    print ('uploading files from \'' + folder_path + '\' to safe \'' + safe_name + '\'...')
    for file in os.listdir(folder_path):
        print ('   uploading \'' + file + '\'... ', end='')
        res = upload_file(safe_name, folder_path + '\/' + file, 'root')
        if res == 201:
            print ('success.')
        elif res == 409:
            print ('failed (file already exists).')
        else:
            print ('failed (' + res + ').')
            
    if logout():
        print('logged-out successfully.')
    
else:
    print('login failed.')

print('\nthank you, come again :)')
