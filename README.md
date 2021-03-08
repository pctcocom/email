# email

## install
* [PHPMailer](https://github.com/PHPMailer/PHPMailer)
```
composer require phpmailer/phpmailer v6.2
composer require pctco/email dev-master
```


## example
### send email
```
Email::send([
   'toEmail'   =>   '',
   'subject'   =>   '',
   'contents'   =>   ''
]);
--------
/**
* @name send
* @describe send email
* @param  string $to        接收人邮件地址
* @param  string $subject     邮件标题
* @param  string $contents  邮件内容 支持HTML格式
* @return Boolean
**/
```
