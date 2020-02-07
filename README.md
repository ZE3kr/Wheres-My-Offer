# Univ-Admit

University Admission Portal Checker

This program can check the portal for each university automatically. Just set up your username and password, and run `check.php`.

You have to a dictionary `/opt/admit`. This program will store the data here to identify changes.

You can set up an IFTTT webhook trigger, or any webhook service, and then forward the message to app notification or phone call. Plus, you should also add `check.php` as a cron job to run the script periodically.

ABSOLUTELY NO GUARANTEE. Use it at your own risk.

Supported Universities:

+ UIUC
+ UMich
+ UNC
+ USC

And more to come.
