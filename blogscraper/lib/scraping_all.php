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

if ( ! wp_next_scheduled( 'scraping_all_hook' ) ) {
    wp_schedule_single_event( time(), 'scraping_all_hook' );
}

function scraping_all_function() {
    //必要なライブラリを読み込む
    require_once( ABSPATH . WPINC . '/pluggable.php' );
    require_once( ABSPATH . WPINC . '/feed.php');
    require_once('simple_html_dom.php');
    
    // ベースになるアメブロフォルダを作成
    $ameblo_folder = ABSPATH. 'ameblo_images/';
    if (!file_exists($ameblo_folder)) {
        mkdir($ameblo_folder);
    }
    
   /*=================================
   // 投稿の日時が同一のものがあるか確認
   // @param $post_date 投稿の日付
   =================================*/
   function check_date($post_date) {
      global $wpdb;    
      $count = 0;
      $sql = $wpdb->prepare("
          SELECT count(*)
          FROM $wpdb->posts
          WHERE post_type = 'ameblo' 
          AND post_date = %s
          AND post_status = 'draft'
          ORDER BY post_date DESC
          LIMIT 25
      ",$post_date);
      $post_title_number = $wpdb->get_var($sql);
      return $post_title_number > 0 ? true : false;
  }





    function insert_all_posts() {
        
        //読み込み設定を取得
        global $opt;
        
        $counter = 0;
        
        $opt = get_option('blog_scraper_options');
        $blog_scraping_url = isset($opt['url']) ? $opt['url']: null;
        $blog_scraping_title = isset($opt['title_class']) ? $opt['title_class']: null;
        $blog_scraping_body = isset($opt['body_class']) ? $opt['body_class']: null;
        $blog_scraping_theme = isset($opt['theme_class']) ? $opt['theme_class']: null;
        $blog_scraping_date = isset($opt['date_class']) ? $opt['date_class']: null;
        
        $blog_post_type = isset($opt['post_type']) ? $opt['post_type']: null;
        
        $blog_url_list = isset($opt['url_list']) ? $opt['url_list']: null;
        
        $current_counter = get_option( 'blog_scraper_options[counter]' );
        
        /*==================================
        一括取得
        ================================== */
        $ameblo_url = file_get_contents( $blog_url_list );
    
        $ameblo_array = explode("\n", $ameblo_url); // 行に分割
        $ameblo_array = array_map('trim', $ameblo_array); // 各行にtrim()をかける
        $ameblo_array = array_filter($ameblo_array, 'strlen'); // 文字数が0の行を取り除く
        $ameblo_array = array_values($ameblo_array); // これはキーを連番に振りなおす
        
        $all_count = count($ameblo_array);
        update_option( 'blog_scraper_options[all_count]', $all_count ); // 現在の行数を保存
        
        $ameblo_array = array_slice($ameblo_array , $current_counter );//前回の続き以降の要素を取得
        
        
        // タイムゾーンを東京に設定
        date_default_timezone_set('Asia/Tokyo');
     
     
        // 投稿を作成
        foreach ($ameblo_array as $item) {
    
                // 取得する記事url
                $get_url = $blog_scraping_url . $item;
                
                if(@file_get_contents($get_url)){
                $html = file_get_html($get_url);
                //配列を作成
                $blog_entry = array();
                
               //タイトルだけ取り出す
               $blog_scraping_title = preg_replace('/\\\/u', '', $blog_scraping_title);
               foreach ($html->find($blog_scraping_title) as $title) {
                    $post_title = $blog_entry['title'] = strip_tags($title);
                };
                    
                //投稿日だけ取り出す
                $blog_scraping_date = preg_replace('/\\\/u', '', $blog_scraping_date);
                foreach ($html->find($blog_scraping_date) as $time) {
                   $date = strip_tags($time);
                   $blog_entry['date'] = date('c', strtotime($date));
                   $post_date =  date('Y-m-d H:i:s', strtotime($date));
                };
                
                //テーマを取り出す
                $blog_scraping_theme = preg_replace('/\\\/u', '', $blog_scraping_theme);
                foreach ($html->find($blog_scraping_theme) as $theme) {
                    $blog_entry['theme'] = strip_tags($theme);
                };
                    
                    
                //本文を取り出す
                $blog_scraping_body = preg_replace('/\\\/u', '', $blog_scraping_body);
                foreach ($html->find($blog_scraping_body) as $entry) {
                    
                    $body = $entry;
                    $attachment = null;
                    
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
                if (check_date($post_date) ) continue;
                $post_value = array(
                    'post_author' => 1,// 投稿者のID。
                    'post_title' => $blog_entry['title'] . $post_title_number ,             // 投稿のタイトル
                    'post_type' => $blog_post_type,             // 投稿タイプ
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
                
                    if( $counter === 0 ){ 
                        $current_counter = get_option( 'blog_scraper_options[counter]' );
                        $counter = $counter + $current_counter ;
                     }
                    $counter ++;
                    update_option( 'blog_scraper_options[counter]', $counter ); // 現在の行数を保存
                
                
                }
        
        //連続取得をやめる 10秒に１回
        //10回に１回は、3分休む
        //50回に１回は、1分休む
        if (($i % 10) == 0) { 
            sleep(120);
        }else if (($i % 50) == 0) { 
            sleep(60);
        }else{
            sleep(10);
        }
        
                }
        
        }//endforeach
    }//end function
    
    
    
    //すべての記事を取得
    insert_all_posts();
}
add_action( 'scraping_all_hook', 'scraping_all_function' );