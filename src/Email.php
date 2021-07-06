<?php
namespace Pctco\Email;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Config;
use think\facade\Request;
use Naucon\File\File;
use Naucon\File\FileWriter;
use Pctco\Info\Ip\Ip;
class Email{
   function __construct(){
      $config = Cache::store('config')->get(md5('app\admin\controller\Config\email\var'));
      $config['config.email.minute'] = ((int)$config['email']['cycle'])/60;

      /**
      * @name 随机验证码
      **/
      $Az = ['A','b','C','D','E','F','g','H','i','J','K','L','M','n','O','P','Q','R','s','T','U','v','W','X','y','z'];
      $Num = ['0','1','2','3','4','5','6','7','8','9'];
      $AzNum = array_merge($Az,$Num);
      $code = '';
      $length = (int)$config['email']['length'];
      switch ($config['email']['code_type']) {
         case '2':
            for ($i=0; $i < $length; $i++) $code = $code.$Az[rand(0, count($Az)-1)];
            break;
         case '3':
            for ($i=0; $i < $length; $i++) $code = $code.$AzNum[rand(0, count($AzNum)-1)];
            break;
         default:
            for ($i=0; $i < $length; $i++) $code = $code.$Num[rand(0, count($Num)-1)];
            break;
      }
      $config['config.email.code'] = $code;

      $this->config = $config;
   }
    /**
    * @name send
    * @describe send email
    * @param  string $to        接收人邮件地址
    * @param  string $subject     邮件标题
    * @param  string $template     邮件模版 如 01
    * @param  string $contents  邮件内容 支持HTML格式
    * @return Boolean
    **/
    public function send($options){
      $options = array_merge([
         'toEmail'   =>   '',
         'subject'   =>   '',
         'template'   =>   0,
         'contents'   =>   '',
         'var'   =>   [
            'code'   =>   $this->config['config.email.code']
         ]
      ],$options);

      if(empty($options['toEmail'])) return false;

      if ($options['template'] !== 0) {
         $template =
         $this->template([
            'name'   =>   $options['template'],
            'event'   =>   'get'
         ]);
         $options['contents'] = $template['content'];
      }

      $options['contents'] = $this->ReplaceVar($options['contents'],$options['var']);

      try{
         $server = $this->config['email']['server'];
         $protocol = $this->config['email']['protocol'];
         $port = $this->config['email']['port'];
         $account = $this->config['email']['account'];
         $password = $this->config['email']['password'];
         $nickname = $this->config['email']['nickname'];


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
            $this->success($options);
            return true;
         }else{
            return false;
            // return $Mailer->errorMessage();
         }
      }catch (phpmailerException $e){
         return false;
      }
   }
   /**
   * @access 判断发送验证码是否正确
   * @param mixed    $email   电子邮箱
   * @param mixed    $template 短信模板  01,02,03
   * @param mixed    $code(email) 验证码
   * @return
   **/
   public function check($data){
      $cycle = (int)$this->config['email']['cycle'];

      $where = [
         'n2' =>  $data['email'],
         'n3'     =>  $data['template'],
         'n4'     =>  $this->config['config.email.code'],
         'type'   =>   'email'
      ];


      Db::name('temporary')
      ->where('type','email')
      ->where('time','<',time() - $cycle)
      ->delete();


      $email =
      Db::name('temporary')
      ->order('time desc')
      ->field('n4,time')
      ->where($where)->find();
      if (empty($email)) {
         return json([
            'headers' => 'Prompt info',
            'status'=>'info',
            'content'=>'验证码已失效',
            'sub' => '当前验证代码已过期。请重新获取验证码！'
         ]);
      }
      if ($email['n4'] != $data['code']) {
         return json([
            'headers' => 'Prompt info',
            'status'=>'info',
            'content'=>'验证码错误',
            'sub' => '验证码不正确。请重新进入！'
         ]);
      }

      Db::name('temporary')
      ->order('time desc')
      ->where($where)->delete();

      return true;
   }
   /**
   * @name success
   * @describe 发送成功
   * @return Array
   **/
   public function success($options){
      Db::name('temporary')->insert([
         'n2' =>  $options['toEmail'],
         'n3'     =>  $options['template'],
         'n4'     =>  $this->config['config.email.code'],
         'type'   =>   'email',
         'time'     =>  time()
      ]);
      return [
         'headers' => 'Prompt info',
         'status'=>'success',
         'content'=>'邮件发送成功，邮件有效期为 '.$this->config['config.email.minute'].' 分钟',
         'sub' => 'Please do not send SMS messages to anyone!',
         // 邮件实际有效时间(分钟)
         'minute'   =>   $this->config['config.email.minute'],
         'second'   =>   60,  // 倒计时秒数
         'length'      =>   $this->config['email']['length']
      ];
   }
   /**
   * @name Email Template
   * @describe 邮件模版
   * @param $name 'system--00'
   * @param $content ''
   * @param $event 默认 false  [save(保存模版内容)，get(获取模版内容)]
   * @return string
   **/
   public function template($options){
      $options = array_merge([
         'name'   =>   'system--00',
         'content'   =>   'Hello, start editing the email template(system--00).',
         'event'   =>   false
      ],$options);


      $path = app()->getRootPath().'entrance'.DIRECTORY_SEPARATOR.'static'.DIRECTORY_SEPARATOR.'template'.DIRECTORY_SEPARATOR.'email'.DIRECTORY_SEPARATOR.$options['name'].'.html';

      $file = new File($path);

      // 模版不存在
      if ($file->exists() === false) {
         $file = new FileWriter($path,'w+');
         $file->write($options['content']);
      }
      // 保存模版内容
      if ($options['event'] === 'save') {
         $file = new FileWriter($path,'w+');
         $file->write($options['content']);
      }
      // 获取模版内容
      if ($options['event'] === 'get') {
         $file = new FileWriter($path, 'r', true);
         $options['content'] = $file->read();
      }
      return [
         'path'   =>   $path,
         'content'   =>   $options['content']
      ];
   }
   /**
   * @name Replace Var
   * @describe 替换变量
   * @param array $data
   * @return string
   **/
   public function ReplaceVar($contents,$var = []){

      $ini = config::get('initialize');
      $website = $ini['config']['website'];


      $auth = config::get('authority');
      $user = $auth['user'];

      $ip = Request::instance()->ip();
      $vars = [
         'SiteName'   =>   $website['name'],
         'SiteDomain'   =>   $website['domain'],
         'UserName'   =>   $user['username'],
         'email'   =>   $website['email'],
         'code'   =>   $this->config['config.email.code'],
         'cycle'   =>   $this->config['config.email.minute'],
         'activity'   =>   '《1024程序员节》',
         'link'   =>   $website['domain'],
         'ip'   =>   $ip,
         'IpLocation' => Ip::HomeAddress($ip,'ipip.net','/')
      ];

      $var = array_merge($vars,$var);

      $contents =
      str_replace([
         '{{SiteName}}',
         '{{SiteDomain}}',
         '{{UserName}}',
         '{{email}}',
         '{{code}}',
         '{{cycle}}',
         '{{activity}}',
         '{{link}}',
         '{{ip}}',
         '{{IpLocation}}'
      ],[
         $var['SiteName'],
         $var['SiteDomain'],
         $var['UserName'],
         $var['email'],
         $var['code'],
         $var['cycle'],
         $var['activity'],
         $var['link'],
         $var['ip'],
         $var['IpLocation']
      ],$contents);

      return $contents;
   }
}
