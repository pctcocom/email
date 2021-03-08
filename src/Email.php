<?php
namespace Pctco\Email;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
/**
 * 数据
 */
class Email{
    /**
    * @name send
    * @describe send email
    * @param  string $to        接收人邮件地址
    * @param  string $subject     邮件标题
    * @param  string $contents  邮件内容 支持HTML格式
    * @return Boolean
    **/
    public static function send($options){
      $options = array_merge([
         'toEmail'   =>   '',
         'subject'   =>   '',
         'contents'   =>   ''
      ],$options);

      if(empty($options['toEmail'])) return false;
      
      try{
         $server = 'smtp.qq.com';
         $protocol = 'default';
         $port = 25;
         $username = '12999026@qq.com';
         $password = 'hnhlfnoqicawbhii';
         $nickname = 'Java Development program';

         $Mailer = new PhpMailer(true);
         //$Mailer->SMTPDebug = 0;                    // 是否调试
         $Mailer->IsSMTP();
         $Mailer->CharSet = 'UTF-8';//编码
         $Mailer->Debugoutput = 'html';// 支持HTML格式
         $Mailer->Host = $server; // host地址
         if ($protocol != 'default') $Mailer->SMTPSecure = $protocol;
         $Mailer->Port = $port;//端口
         $Mailer->SMTPAuth = true;
         $Mailer->Username = $username;//用户名
         $Mailer->Password = $password;//密码
         $Mailer->SetFrom($username,$nickname);//发件人地址, 发件人名称
         $Mailer->AddAddress($options['toEmail']);//收信人地址
         $Mailer->Subject = $options['subject'];//邮件标题
         $Mailer->MsgHTML($options['contents']);
         if ($Mailer->Send()){
            return true;
         }else{
            return false;
            // return $Mailer->errorMessage();
         }
      }catch (phpmailerException $e){
         return false;
      }
   }
}
