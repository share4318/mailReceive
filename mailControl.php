<?php
/**
 * Created by PhpStorm.
 * User: Hety <Hetystars@gmail.com>
 * Date: 12/14/2017
 * Time: 8:25 PM
 */


include('receiverMail.class.php');

class mailControl
{
    /**
     * 定义系统常量
     * mailServer  腾讯企业邮箱 imap.exmail.qq.com port 143
     * @var string
     */
    protected
        $mailAccount = '',
        $mailPasswd = '',
        $mailAddress = '',
        $mailServer = 'imap.exmail.qq.com',
        $serverType = 'pop3',
        $port = '143',
        $now = '',
        $savePath = '',
        $webPath = '';

    /**
     * @var receivemail
     */
    static private $instance;

    /**
     * mailControl constructor.
     */
    public function __construct()
    {
        header('Content-type: text/html;charset=UTF-8');
        ignore_user_abort();
        set_time_limit(0);
        date_default_timezone_set('Asia/Shanghai');
        error_reporting(-1);
        $this->now = date('Y-m-d H:i:s');
        $this->setSavePath();
        !self::$instance && self::$instance = new receivemail($this->mailAccount, $this->mailPasswd, $this->mailAddress, $this->mailServer, $this->serverType, $this->port, true);
    }

    /**
     * @param $pattern
     * @param bool $isSeen
     * @param bool $fromHead
     * @param bool $fromContent
     * @param bool $isDelete
     * @param bool $isMove
     * @param string $moveFile
     * @return array|bool
     */
    public function mailReceived($pattern, $isSeen = true, $fromHead = false, $fromContent = false, $isDelete = false, $isMove = false, $moveFile = 'db_address')
    {
        self::help($pattern);
        if (!$res = self::$instance->connect()) {
            return array('msg' => 'Error: Connecting to mail server');
        }
        $tot = self::$instance->getTotalMails();
        echo sprintf('总的邮件数量为%d', $tot) . PHP_EOL;
        //如果信件数为0,显示信息
        if ($tot < 1) {
            return array('msg' => 'No Message for ' . $this->mailAccount);
        }
        $res = array('msg' => "Total Mails:: $tot<br>");

        for ($i = $tot; $i > 0; $i--) {
            $head = self::$instance->getHeaders($i);
            if ($isSeen && $head['seen'] !== 'U') break;
            echo sprintf('正在读取邮件主题为%s的邮件', $head['subject']) . PHP_EOL;
            if ($fromHead && strpos($head[key($pattern)], current($pattern)) !== false) {
                $body = self::$instance->getBody($i, $this->webPath);
                $files = self::$instance->GetAttach($i, $this->savePath);
                $res['mail'][] = array('head' => $head, 'body' => $body, 'attachList' => $files);
                $isDelete && self::$instance->deleteMails($i, false);
                continue;
            }
            $body = self::$instance->getBody($i, $this->webPath);
            if ($fromContent && false !== strpos($body[key($pattern)], current($pattern))) {
                $files = self::$instance->GetAttach($i, $this->savePath);
                $res['mail'][] = array('head' => $head, 'body' => $body, 'attachList' => $files);
                $isDelete && self::$instance->deleteMails($i, false);
                continue;
            }
            if (empty($pattern)) {
                $files = self::$instance->GetAttach($i, $this->savePath);
                $isDelete && self::$instance->deleteMails($i, false);
                $isMove && self::$instance->move_mails($i, $moveFile);
                $res['mail'][] = array('head' => $head, 'body' => $body, 'attachList' => $files);
            }

        }
        echo sprintf('有效邮件总数为%d', self::$instance->totalValid) . PHP_EOL;
        //Close Mail Box
        self::$instance->close_mailbox();
        return $res;
    }

    /**
     * @param $pattern
     */
    public static function help($pattern)
    {
        if (empty($pattern)) {
            echo <<<help
        参数pattern示例
        1.header 
            [fromBy] => notice@gmail.com
            [fromName] => 慢查询SQL
            [ccList] => 
            [toNameOth] => ����ѯSQL
            [subject] => RDS慢查询SQL
            [mailDate] => 2017-12-12 15:16:04
            [udate] => 1513062964
            [toList] => 15487855555@qq.com,1258444@163.com,xx@126.com
         2.body
          [text] => db_address慢查询语句具体SQL语句,请查看excel附件!
          [img] => 
         3.没有任何匹配模大，将搜索所有邮件
help;
        } else {
            echo '将要匹配的的类目为"' . current($pattern) . '"匹配字符为"' . key($pattern) . '"';
        }
        echo PHP_EOL;
    }

    /**
     * @param $boxName
     */
    public function creatBox($boxName)
    {
        self::$instance->creat_mailbox($boxName);
    }

    /**
     * Set save path.
     * @access public
     * @return void
     */
    public function setSavePath()
    {
        empty($this->savePath) && $this->savePath = __DIR__;
        if (!file_exists($this->savePath)) {
            @mkdir($this->savePath, 0777, true);
            touch($this->savePath . 'index.html');
        }
    }

    /**
     * @param $mid
     * @return array|bool
     */
    public function deleteMail($mid, $isALL)
    {
        $res = self::$instance->connect();
        if (!$res) {
            return array('msg' => 'Error: Connecting to mail server');
        }
        return self::$instance->deleteMails($mid, $isALL);
    }

    /**
     * @return array|bool
     */
    public function mailList()
    {
        $res = self::$instance->connect();
        if (!$res) {
            return array('msg' => 'Error: Connecting to mail server');
        }
        return self::$instance->mailList();
    }


}

$obj = new mailControl();
//收取邮件
$res = $obj->mailReceived(['text' => 'db_address'], true, false, true, true);
//print_r($res);

//创建邮箱
//  self::$instance->creatBox('readyBox');