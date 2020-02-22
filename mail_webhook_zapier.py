import requests

'''
Need these inputs:
    time: {{zap_meta_timestamp}}
    from: {{sender_emailAddress_address}}
    subject: {{subject}}
    body: {{bodyPreview}}
'''

if ('edu' in input['subject']) or ('ubc.ca' in input['subject']) :
    payload = {'time': input['time'], 'from': input['from'], 'subject': input['subject'], 'body': input['body']}
    payload_param = {'key': 'uegkf2HHitKg'}
    r = requests.post('https://api.tloxygen.com/update_admit.php', data=payload, params=payload_param)
    print(r.text)

    output = {'success': True}
else:
    output = {'success': False}
