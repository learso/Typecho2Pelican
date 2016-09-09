<?php
if (!defined('__TYPECHO_ROOT_DIR__')) exit;
/**
 * typecho发布文章同时发布到pelican
 * 
 * @package Typecho2Pelican 
 * @author learso
 * @version 0.0.1
 * @link http://learso.com
 */
class Typecho2Pelican_Plugin implements Typecho_Plugin_Interface
{
    /**
     * 激活插件方法,如果激活失败,直接抛出异常
     * 
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function activate()
    {
        Typecho_Plugin::factory('Widget_Contents_Post_Edit')->finishPublish = array('Typecho2Pelican_Plugin', 'render');
        Typecho_Plugin::factory('Widget_Contents_Page_Edit')->finishPublish = array('Typecho2Pelican_Plugin', 'render');
        return "插件安装成功，请进入设置配置相关信息";
    }

    /**
     * 禁用插件方法,如果禁用失败,直接抛出异常
     * 
     * @static
     * @access public
     * @return void
     * @throws Typecho_Plugin_Exception
     */
    public static function deactivate(){
        return "插件卸载成功";    
    }

    /**
     * 获取插件配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form 配置面板
     * @return void
     */
    public static function config(Typecho_Widget_Helper_Form $form)
    {
        /** 配置欢迎话语 */
        $contentPath = new Typecho_Widget_Helper_Form_Element_Text('contentPath', NULL, 'content', _t('pelican内容目录'), '把本目录链接到真正的pelican目录下');            
        $form->addInput($contentPath);

        $summary = new Typecho_Widget_Helper_Form_Element_Text('summary', NULL, '300', _t('摘要长度'), '每篇日志摘要的长度，0表示全文输出');            
        $form->addInput($summary);
    }

    /**
     * 个人用户的配置面板
     * 
     * @access public
     * @param Typecho_Widget_Helper_Form $form
     * @return void
     */
    public static function personalConfig(Typecho_Widget_Helper_Form $form){}

    /**
     * 插件实现方法
     * 
     * @access public
     * @return void
     */
    public static function render($contents, $class)
    {
        //如果文章属性为隐藏或滞后发布
        if( 'publish' != $contents['visibility'] || $contents['created'] > time()){
            return;
        }

        //获取系统配置
        $options = Helper::options();

        $contentPath = $options->plugin('Typecho2Pelican')->contentPath;
        //判断是否配置好path
        if( is_null($contentPath) ){
            return;
        }
        $path = __TYPECHO_ROOT_DIR__ . '/' . $contentPath;
        //创建上传目录
        if (!is_dir($path . '/posts')) {
            if (!self::makeContentDir($path . '/posts')) {
                return false;
            }

        }
        if (!is_dir($path . '/pages')) {
            if (!self::makeContentDir($path . '/pages')) {
                return false;
            }
        }

        if($contents['type'] == 'post')
        {
            $fileName = $path . '/posts/' . $contents['slug'] . '.md';
        }
        else
        {
            $fileName = $path . '/pages/' . $contents['slug'] . '.md';
        }

        // 标题
        $outContent = "Title: " . $contents['title'] . "\n";
        // 创建日期
        $created = $contents['created'];
        if( $created != "" ) {
            $postDate = new Typecho_Date($created);
        }
        else {
            $postDate = new Typecho_Date(Typecho_Date::gmtTime());
        }
        $outContent = $outContent . 'Date: ' . $postDate->format('Y-m-d H:i:s') . "\n";
        // 分类
        $outContent = $outContent . 'Category: ';                
        /** 取出已有category */
        $db = Typecho_Db::get();
        $categories = Typecho_Common::arrayFlatten($db->fetchAll(
                    $db->select('table.metas.name')
                    ->from('table.metas')
                    ->join('table.relationships', 'table.relationships.mid = table.metas.mid')
                    ->where('table.relationships.cid = ?', $class->cid)
                    ->where('table.metas.type = ?', 'category')), 'name');       
        $iCnt = 0;
        foreach( $categories as $category ) {
            $outContent = $outContent . $category;
            $iCnt++;

            if($iCnt != count($categories) ) {
                $outContent = $outContent . ",";
            }
        }
        $outContent = $outContent . "\n";
        // 标签
        if( $contents['tags'] != "" ) {
            $outContent = $outContent . 'Tags: ' . $contents['tags'] . "\n";
        }
        // slug
        $outContent = $outContent . 'Slug: ' . $contents['slug'] . "\n";
        // 摘要
        $summary = $options->plugin('Typecho2Pelican')->summary;
        $intSum = intval( $summary );
        if( $intSum != 0 ) {
            $outContent = $outContent . 'summary: ' . self::trimall(mb_substr( $contents['text'], 0, $intSum ));
        }

        $outContent = $outContent . "\n\n\n\n";
        // 正文
        $outContent = $outContent . $contents['text'];

        file_put_contents($fileName, $outContent);

        $okFile =  $path . '/ok';
        file_put_contents($okFile, '');
    }

    /**
     * 创建上传路径
     *
     * @access private
     * @param string $path 路径
     * @return boolean
     */
    private static function makeContentDir($path)
    {
        $path = preg_replace("/\\\+/", '/', $path);
        $current = rtrim($path, '/');
        $last = $current;

        while (!is_dir($current) && false !== strpos($path, '/')) {
            $last = $current;
            $current = dirname($current);
        }

        if ($last == $current) {
            return true;
        }

        if (!@mkdir($last)) {
            return false;
        }

        $stat = @stat($last);
        $perms = $stat['mode'] & 0007777;
        @chmod($last, $perms);

        return self::makeContentDir($path);
    }

    /**
     * 去空格和换行等特殊符号
     *
     * @access private
     * @param string $str 原字符串
     * @return 处理后的字符串
     */
    private static function trimall($str){
        $qian=array(" ","　","\t","\n","\r", "#");
        return str_replace($qian, '', $str);   
    }
}
