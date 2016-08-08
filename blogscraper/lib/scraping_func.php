<?php
/*
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


//cron スケジュール登録
if ( ! wp_next_scheduled( 'scraping_hook' ) ) {
    //wp_schedule_single_event( time(), 'scraping_hook' );
    wp_schedule_event( time(), 'daily', 'scraping_hook');
}

function scraping_function() {
    
    //必要なライブラリを読み込む
    require_once( ABSPATH . WPINC . '/pluggable.php' );
    require_once( ABSPATH . WPINC . '/feed.php');
    require_once('simple_html_dom.php');
    
    // ベースになるアメブロフォルダがない場合は作成
    $ameblo_folder = ABSPATH. 'ameblo_images/';
    if (!file_exists($ameblo_folder)) {
        mkdir($ameblo_folder);
    }
    
     /*=================================
     // 投稿の日時が同一のものがあるか確認
     // @param $post_date 投稿の日付
     =================================*/
     function has_title($post_date) {
        global $wpdb;    
        $count = 0;
        $sql = $wpdb->prepare("
            SELECT count(*)
            FROM $wpdb->posts
            WHERE post_type = 'ameblo' 
            AND post_date = %s
            AND post_status = 'draft'
            ORDER BY post_date DESC
            LIMIT 100
        ",$post_date);
        $post_title_number = $wpdb->get_var($sql);
        return $post_title_number > 0 ? true : false;
    }


    //フィードから新着情報を読み込む
    function insert_feed_posts() {
        
        //読み込み設定を取得
        $opt = get_option('blog_scraper_options');
        $blog_rss_feed = $opt['feed'];
        $blog_post_type = isset($opt['post_type']) ? $opt['post_type']: null;
        
        $scraping_setting = isset($opt['setting']) ? $opt['setting'] : null;
        
        if( isset($scraping_setting)){
            $file = dirname(__FILE__) . '/txt/'. $scraping_setting .'.txt';
            file_get_contents($file);
            //配列に格納
            $array = @file($file, FILE_IGNORE_NEW_LINES);
            
            
        } else {
            
            
            $blog_scraping_body = isset($opt['body_class']) ? $opt['body_class']: null;
            $blog_scraping_theme = isset($opt['theme_class']) ? $opt['theme_class']: null;
            $blog_scraping_theme = isset($opt['theme_class']) ? $opt['theme_class']: null;
            
        }
        
        


        // ブログのオブジェクトを取得
        $rss = fetch_feed($blog_rss_feed);
     
        // フィードが生成されていない、または、フィードのエントリーが0の場合は関数終了
        if (is_wp_error($rss) || $rss->get_item_quantity() == 0) {
            return;
        }
     
        // 30分ごとにキャッシュするよう設定
        $rss->set_cache_duration(1800);
        $rss->init();
        
        // タイムゾーンを東京に設定
        date_default_timezone_set('Asia/Tokyo');
     
     
        // 各記事を投稿用に加工する
        foreach ($rss->get_items( 0, $items ) as $item) {
            
                
                $blog_entry = array();                        //配列を作成
                $get_url = $item->get_link();              // 取得する記事url
                $post_title = $item->get_title();          // 取得する記事タイトル
                if ( strstr($post_title, 'PR')) continue; // PRが含まれる場合は飛ばす
                
                
                //投稿日を設定
                $post_date = $item->get_date('Y-m-d H:i:s');
                $blog_entry['date'] = date('c', strtotime($post_date));
                
                //htmlの中身を取得
                $html = file_get_html($get_url);
                
                //テーマを取り出す
                $blog_scraping_theme = preg_replace('/\\\/u', '', $blog_scraping_theme);
                foreach ($html->find($blog_scraping_theme) as $theme) {
                    $blog_entry['theme'] = strip_tags($theme);
                };
                
                //本文を取り出す
                $blog_scraping_body = preg_replace('/\\\/u', '', $blog_scraping_body);
                foreach ($html->find($blog_scraping_body) as $entry) {
                    
                    $body = $entry;

                    
                   //画像を取得
                    $i = 0;
                    foreach ($entry->find("img") as $image) {
                     $i++;
                            
                            //画像タグを取得
                        
                        //$blog_entryの配列に、画像URLそのものを追加
                        $blog_entry['image'][$i]['url'] = $img_result = $image-> src;
                        
                        //画像urlを解析して、ファイル名などのパーツに分ける
                        $pathData = pathinfo($img_result);
                        
                        //$blog_entry['image'][$i]['filename']　に、ファイルのベースネームを格納
                        $blog_entry['image'][$i]['filename'] = $pathData["basename"]; //ファイル名
                        
                        //アメブロの場合は、日付別にフォルダを分けたい
                        //$blog_entry['image'][$i]['path']に、保存先を格納
                        if( preg_match("/[0-9]{8}/i", $pathData["dirname"], $dataArr)){
                            $blog_entry['image'][$i]['path'] = $dataArr[0];//日付
                        } else{
                            $blog_entry['image'][$i]['path'] = 'other';
                            }
                        
                        /*画像をサーバーに保存 =======================*/
                        //画像のurl = $img_result
                        //$src_data = $blog_entry['image'][$i]['url'];
                        
                        //保存するフォルダ名
                        $save_folder = ABSPATH. 'ameblo_images/' .$blog_entry['image'][$i]['path'];
                        
                        //保存するファイル名
                        $save_filename = ABSPATH. 'ameblo_images/' .$blog_entry['image'][$i]['path'] .'/'. $blog_entry['image'][$i]['filename'];
                        
                        //echo $img_result;
                        
                        // 保存先フォルダの作成する。
                        if (!file_exists($save_folder)) {
                            mkdir($save_folder);
                        }
                        
                        //画像ファイルが無い時は取得
                        if (!file_exists($save_filename)) {
                            $file_get_contents = file_get_contents($img_result);
                            file_put_contents($save_filename,$file_get_contents);
                        }
                        
                        
                        //１番最初の画像をアイキャッチに設定
                        $emoji_count = preg_match('/(char)|(emoji)|(xyz)/', $img_result);
                        if( ($emoji_count === 0) && !is_array($attachment)){
                            $upload_dir = wp_upload_dir();
                            $thumb_path = $upload_dir['path'] . '/' . $blog_entry['image'][$i]['filename'];
                            
                            if (!file_exists($thumb_path)) {
                                $file_set_thumbnail = file_get_contents($img_result);
                                file_put_contents($thumb_path, $file_set_thumbnail);
                            }
                            
                            $wp_filetype = wp_check_filetype($img_result, null );
                            $attachment = array(
                                'post_mime_type' => $wp_filetype['type'],
                                'post_title' => sanitize_file_name($blog_entry['image'][$i]['filename']),
                                'post_content' => '',
                                'post_status' => 'inherit'
                            );
                        }
                        
                        
                        
                        /*bodyの画像を置き換える =======================*/
                        $file_paht = home_url(). '/ameblo_images/' . $blog_entry['image'][$i]['path'] .'/'. $blog_entry['image'][$i]['filename'];
                        $body = str_replace( $img_result, $file_paht , $body);
                
                    };
                                
                    $pattern="/style=\".*?\"|style='.*?'/i";
                    $body=preg_replace($pattern,"",$body);
                    
                    $blog_entry['body'] =strip_tags($body , '<p><img><a><div><br>');
                }// endforeach
    
    
             /*=================================
             // 投稿の日時が同一のものがあればスキップ
             // @param $post_date 投稿の日付
             =================================*/
                if (has_title($post_date) ) continue;
                $post_value = array(
                    'post_author' => 1,// 投稿者のID。
                    'post_title' => $post_title,             // 投稿のタイトル
                    'post_type' => $blog_post_type,                          // 投稿タイプ
                    'post_content' => $blog_entry['body'],     // 投稿の本文
                    'post_status' => 'draft',                            // 公開ステータス
                    'post_date'      => $blog_entry['date'],             // 投稿の作成日時。
                    'post_date_gmt'  =>$blog_entry['date'],     // 投稿の作成日時（GMT）。
                    'post_name'     =>basename($get_url , '.html')       // 投稿のスラッグ。
                );
                $insert_id = wp_insert_post($post_value);
                 if($insert_id) {
                     
                    //テーマを登録
                    wp_set_object_terms($insert_id, $blog_entry['theme'], 'theme');
                    
                    
                    //アイキャッチを登録
                    if(!empty ($attachment) ){
                        $attach_id = wp_insert_attachment( $attachment, $thumb_path, $insert_id );
                        require_once(ABSPATH . 'wp-admin/includes/image.php');
                        $attach_data = wp_generate_attachment_metadata( $attach_id, $thumb_path );
                        wp_update_attachment_metadata( $attach_id, $attach_data );
                        set_post_thumbnail( $insert_id, $attach_id );
                    }
                    
                }
                
                $attachment = null;
        
        //連続取得をやめる
        sleep(2);
        }//endforeach
    }//end function
    
    insert_feed_posts();


}
add_action( 'scraping_hook', 'scraping_function' );