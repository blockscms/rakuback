<?php
/*
Plugin Name: Blog Scraper
Plugin URI: 
Description: blogをバックアップ
Version: 1.0.0
Author:Healing Solutions
Author URI: https://www.healing-solutions.jp/
License: GPL2
*/

/*  Copyright 2016 Healing Solutions (email : info@healing-solutions.jp)
 
    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License, version 2, as
     published by the Free Software Foundation.
 
    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
 
    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
class blog_scraping_Class {
    function __construct() {
      add_action('admin_menu', array($this, 'add_pages'));
      add_action('init',array($this, 'add_scraping_cron'), 999);
    }
    function add_pages() {
      add_options_page('ブログ読込み設定','ブログ読込み設定',  'level_8', __FILE__, array($this,'blog_scraping_option_page'), '', 26);
    }
    
    
    
    function blog_scraping_option_page() {
        
        
         $blog_scraping_array = array(
         
            ['setting_title'] => '',
            ['setting_list'] => array(
                'title_class' => 'a.skinArticleTitle',
                'body_class' => '.articleText',
                'theme_class' => '.articleTheme a[rel="tag"]',
                'date_class' => 'span.articleTime time'
            ),
            
            ['setting_title'] => '',
            ['setting_list'] => array(
                'title_class' => '.skin-entryTitle a',
                'body_class' => '.skin-entryBody',
                'theme_class' => '.skin-entryThemes a[rel="tag"]',
                'date_class' => '.skin-entryPubdate time'
            ),
         );
        
        
        
        
        
        
        
        
        
        
        
        //$_POST['blog_scraper_options'])があったら保存
        if ( isset($_POST['blog_scraper_options'])) {
            check_admin_referer('shoptions');
            $opt = $_POST['blog_scraper_options'];
            update_option('blog_scraper_options', $opt);
            ?><div class="updated fade"><p><strong><?php _e('保存完了'); ?></strong></p></div><?php
        }
        ?>
        <div class="wrap">
        <script type="text/javascript">
       jQuery(document).ready(function() {
    var formfield;
    //メタボックス内のボタンからmedia_upload.phpを呼び出す
    jQuery('#upload_image_button').click(function() {
        jQuery('#upload_image').addClass('image');
        formfield = jQuery('.image').attr('name');
        tb_show('', 'media-upload.php?type=image&TB_iframe=true');
        return false;
    });
 
    window.original_send_to_editor = window.send_to_editor;
    //メディアアップローダーからきた変数htmlを各々へ挿入
    window.send_to_editor = function(html){
        //「"」を「'」に変換
        html = new String(html).replace(/\"/g, "'");
        if (formfield) {
            jQuery('.image').val(html);
            tb_remove();
            jQuery('#upload_image').removeClass('image');
            //挿入した画像プレビューさせるエリアへソースをいれる
            jQuery('#uploadedImageView').html(html);
        } else {
            window.original_send_to_editor(html);
        }
    };
});
        </script>
        
        <div id="icon-options-general" class="icon32"><br /></div><h2>ブログ読み込み設定</h2>
            <form action="" method="post">
                <?php
                wp_nonce_field('shoptions');
                $opt = get_option('blog_scraper_options');
                $blog_scraping_url = isset($opt['url']) ? $opt['url']: null;
                $blog_scraping_feed = isset($opt['feed']) ? $opt['feed']: null;
                
                $blog_scraping_title = isset($opt['title_class']) ? $opt['title_class']: null;
                $blog_scraping_title = preg_replace('/\\\/u', '', $blog_scraping_title);
                
                $blog_scraping_body = isset($opt['body_class']) ? $opt['body_class']: null;
                $blog_scraping_body = preg_replace('/\\\/u', '', $blog_scraping_body);
                
                
                $blog_scraping_theme = isset($opt['theme_class']) ? $opt['theme_class']: null;
                $blog_scraping_theme = preg_replace('/\\\/u', '', $blog_scraping_theme);
                
                $blog_scraping_date = isset($opt['date_class']) ? $opt['date_class']: null;
                $blog_scraping_date = preg_replace('/\\\/u', '', $blog_scraping_date);
                
                $blog_scraping_all = isset($opt['scraping_all']) ? $opt['scraping_all']: null;
                $blog_url_list = isset($opt['url_list']) ? $opt['url_list']: null;
                
                $blog_post_type = isset($opt['post_type']) ? $opt['post_type']: null;
                
                $scraping_all_counter = isset($opt['counter']) ? $opt['counter']: 0;
                
                ?> 
                <table class="form-table">
                
                <tr valign="top">
                        <th scope="row"><label for="inputtext">投稿する場所</label></th>
                        <td>
                        <select name="blog_scraper_options[post_type]" id="post_type">
                        <option value=""></option>
                <?php
                $post_types = get_post_types( '', 'names' );
                $post_type_array = array(
                            'pages',
                            'revision',
                            'attachment',
                            'cfs',
                            'nav_menu_item',
                            'np-redirect',
                            'smart-custom-fields',
                            'parts',
                            'schedule',
                            'keyv',
                            'wpdmpro',
                            'wpcf7_contact_form',
                            'page'
                        );
                        
                foreach ( $post_types as $post_type ) {
                   if( ! array_search($post_type,$post_type_array)){
                       ?>
                       <option value="<?php echo $post_type; ?>"<?php if ( $blog_post_type === $post_type ): ?>selected<?php endif; ?>><?php echo esc_html(get_post_type_object($post_type)->label ); ?></option>
                <?php
                   };
                }//endforeach
                ?>
                </select>
               <br><span class="description">途中で変えないようにしてください</span>
                </td>
                    </tr>
                
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">ブログURL</label></th>
                        <td><input name="blog_scraper_options[url]" type="text" id="inputtext" value="<?php  echo $blog_scraping_url ?>" class="regular-text" /></td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">ブログRSS</label></th>
                        <td><input name="blog_scraper_options[feed]" type="text" id="inputtext" value="<?php  echo $blog_scraping_feed ?>" class="regular-text" />
                        
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">タイトルの場所</label></th>
                        <td><input name="blog_scraper_options[title_class]" type="text" id="inputtext" value="<?php  echo $blog_scraping_title ?>" class="regular-text" /><br>
<span class="description">ameblo の場合は「.skin-entryTitle a」など</span>
                        </td>
                    </tr>
                    
                    
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">本文の場所</label></th>
                        <td><input name="blog_scraper_options[body_class]" type="text" id="inputtext" value="<?php  echo $blog_scraping_body ?>" class="regular-text" />
                        <br><span class="description">ameblo の場合は「.skin-entryBody」など</span></td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">テーマの場所</label></th>
                        <td><input name="blog_scraper_options[theme_class]" type="text" id="inputtext" value="<?php echo $blog_scraping_theme ?>" class="regular-text" /><br>
                        <span class="description">ameblo の場合は「.skin-entryThemes a[rel='tag']」など</span>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">投稿日の場所</label></th>
                        <td><input name="blog_scraper_options[date_class]" type="text" id="inputtext" value="<?php echo $blog_scraping_date ?>" class="regular-text" /><br>
                        <span class="description">ameblo の場合は「span.articleTime time」など</span>
                        </td>
                    </tr>
                    
                    <tr valign="top">
                        <th scope="row"><label for="inputtext">全体読み込み</label></th>
                        <td>
                        <input type="checkbox" id="" name="blog_scraper_options[scraping_all]" value="1" <?php if($blog_scraping_all){ ?>checked="checked"<?php } ?>>
                        
                        <br>
                        <span class="description">すべてのブログの読み込みを有効にする　※URLリストファイルが必要です</span>
                        </td>
                    </tr>
                    
                    <tr valign="top"><th scope="row">リストファイルのパス</th>
                        <td>
                        <input name="blog_scraper_options[url_list]" type="text" id="inputtext" value="<?php echo $blog_url_list ?>" class="regular-text" />
                        <br>
                        <span class="description">一括読み込みをするリストファイル</span>
                        <?php
                        echo '<p>'.get_option( 'blog_scraper_options[all_count]' ) .'件中　現在' .  get_option( 'blog_scraper_options[counter]' ) .'件目まで取得済み</p>';
                        ?>
                        </td>
                    </tr>
                    
                    
                </table>
                <p class="submit"><input type="submit" name="Submit" class="button-primary" value="変更を保存" /></p>
            </form>
        <!-- /.wrap --></div>
        <?php
        
    }


    function add_scraping_cron() {
        $opt = get_option('blog_scraper_options');
        
        if( !empty($opt['post_type'] )){
            require( 'lib/scraping_func.php' );
            
            if( $opt['scraping_all'] ){
                require( 'lib/scraping_all.php' );
            }else{
                update_option( 'blog_scraper_options[counter]', 0 ); // 行数を一度リセット
                }
        }//$opt['post_type']
    }//add_scraping_cron
    
    
}
$obj = new blog_scraping_Class();