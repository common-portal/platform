/*

* MX.NSDB.COM Mailer API

*

* Required params: email_to_emailaddress, email_subject, email_html_message

* Optional params: email_to_name, email_from_name, email_from_emailaddress,

* email_replyto_name, email_replyto_emailaddress, cc_email_addresses, bcc_email_addresses

*

* SAMPLE POST (JSON):

* curl -X POST https://mx.nsdb.com:8443/common_mailer_gateway_api.php\

* -H "Content-Type: application/json" \

* -d '{

* "mx_nsdb_com_username": "mailer@nsdb.com",

* "email_to_emailaddress": "$recipient_email_address",

* "email_to_name": "$recipient_name",

* "email_from_emailaddress": "$BRAND_EMAIL_ADDRESS",

* "email_from_name": "$BRAND_NAME",

* "email_subject": "$emial_template_subject",

* "email_html_message": "$emial_template_message"

* }'

*/