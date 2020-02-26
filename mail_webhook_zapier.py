import requests

'''
Need these inputs:
    time: {{zap_meta_timestamp}}
    from: {{sender_emailAddress_address}}
    subject: {{subject}}
    body: {{bodyPreview}}
'''

if ('edu' in input['from']) or ('ubc.ca' in input['from']) :
    payload = {'time': input['time'], 'from': input['from'], 'subject': input['subject'], 'body': input['body']}
    r = requests.post('https://example.com/admit_webhook.php', data=payload, params=payload_param)
    print(r.text)

    output = {'success': True}
else:
    output = {'success': False}
