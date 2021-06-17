<?php
namespace Pctco\Email;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use think\facade\Cache;
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
         $var = Cache::store('config')->get(md5('app\admin\controller\Config\email\var'));

         $server = $var['email']['server'];
         $protocol = $var['email']['protocol'];
         $port = $var['email']['port'];
         $account = $var['email']['account'];
         $password = $var['email']['password'];
         $nickname = $var['email']['nickname'];


         $Mailer = new PhpMailer(true);
         //$Mailer->SMTPDebug = 0;                    // 是否调试
         $Mailer->IsSMTP();
         $Mailer->CharSet = 'UTF-8';//编码
         $Mailer->Debugoutput = 'html';// 支持HTML格式
         $Mailer->Host = $server; // host地址
         if ($protocol != 'Default') $Mailer->SMTPSecure = $protocol;
         $Mailer->Port = $port;//端口
         $Mailer->SMTPAuth = true;
         $Mailer->Username = $account;//用户名
         $Mailer->Password = $password;//密码
         $Mailer->SetFrom($account,$nickname);//发件人地址, 发件人名称
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
