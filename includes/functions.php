<?php

    /* Add custom menu to admin menu */
    function import_videos_admin_menu_hook()
    {
        add_submenu_page('tools.php', __('Import Videos'), __('Import Videos'), 'manage_options', 'upload_video', 'import_video_upload_video_hook' );
    }
    
    function import_videos_admin_enqueue_scripts_hook()
    {
        wp_register_style('import_videos_admin_style',IMPORT_VIDEOS_PLUGIN_URL.'/css/admin_style.css');
        wp_enqueue_style('import_videos_admin_style');
    }
    
    function import_video_upload_video_hook()
    {
?>
        <div class='wrap white-box'>
            <h1>Import videos</h1>
            
            <?php
            
                if(isset($_REQUEST['i']) && $_REQUEST['i'] != '')
                {
                    $message = '';
                    $class = 'error';
                    switch($_REQUEST['i'])
                    {
                        case 'nofile':
                            $message = '<strong>Error: </strong>Please upload a file.';
                            break;
                        case 'invalidfiletype':
                            $message = '<strong>Error: </strong>Invalid file type. Can upload .xml files only.';
                            break;
                        case 'invalidimportto':
                            $message = '<strong>Error: </strong>Please select Upload as option.';
                            break;
                        case 'invalidxml':
                            $message = '<strong>Error: </strong>Invalid XML file.';
                            break;
                        case 'novid':
                            $message = '<strong>Error: </strong>No videos found to import.';
                            break;
                        case 'success':
                            $message = 'Videos imported successfully.';
                            $class = 'updated';
                            break;
                    }
                    
                    if($message != '')
                    {
            ?>
                        <div class='<?php echo $class; ?>'><p><?php echo $message; ?></p></div>
            <?php
                    }
                }
            
            ?>
            
            <form name='frmImportVideos' id='frmImportVideos' method='post' action='' enctype='multipart/form-data'>
                <?php
                    wp_nonce_field('video_details_import','video_details_import_nonce');
                ?>
                <input type='hidden' name='action' value='import_videos' />
                <table class="form-table">
                    <tbody>
                        <tr>
                            <th scope='row'>Upload Video XML: </th>
                            <td>
                                <input type='file' name='video_xml' id='video_xml' />
                            </td>
                        </tr>
                        <tr>
                            <th scope='row'>Upload As: </th>
                            <td>
                                <p><input type='radio' name='upload_as' id='upload_as_post' value='1' checked='checked' /> <label>Import videos as blog post entries. <br/><small>Categorized by series title</small></label></p>
                                <p><input type='radio' name='upload_as' id='upload_as_page' value='2' /> <label>Import videos as pages.<br/><small>One new page per video series, and each page will contain a list of all video titles in that series, with the video embedded below the titles with description.</small></label></p>
                                <p><input type='radio' name='upload_as' id='upload_as_page' value='3' /> <label>Import videos as pages (Single video per page).<br/><small>One new page per video will be generated, and each page will contain video title with the video embedded below the titles with description.</small></label></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" value="Import Videos" class="button button-primary" id="submit" name="submit">
                </p>
            </form>
        </div>
<?php
    }
    
    function import_videos_init_hook()
    {
        if(is_admin() && isset( $_POST['video_details_import_nonce'] ) && wp_verify_nonce( $_POST['video_details_import_nonce'], 'video_details_import' ) && isset($_POST['action']) && $_POST['action'] == 'import_videos')
        {
            if(isset($_FILES['video_xml']) && isset($_FILES['video_xml']['name']) && $_FILES['video_xml']['name'] != '')
            {
                $arrExt = explode('.', $_FILES['video_xml']['name']);
                $ext = array_pop($arrExt);
                if(strtolower($ext) == 'xml')
                {
                    if(isset($_POST['upload_as']) && in_array($_POST['upload_as'], array('1','2','3')))
                    {
                        //$arrSproutVideoIds = get_option('sproutvideoids',array());
                        
                        $isPost = $_POST['upload_as'] == '1' ? true : false;
                        
                        $fileName = $_FILES['video_xml']['tmp_name'];
                        $objDom = new DOMDocument();
                        $objDom->load($fileName);
                        
                        $arrVideoParent = $objDom->getElementsByTagName('videos');
                        
                        if($arrVideoParent->length > 0)
                        {
                            $arrVideos = $arrVideoParent->item(0)->getElementsByTagName('video');
                            
                            if($arrVideos->length > 0)
                            {
                                if(!$isPost)
                                    $arrPagesData = array();
                                
                                for($i = 0; $i < $arrVideos->length; $i++)
                                {
                                    $curVideo = $arrVideos->item($i);

                                    $id = $curVideo->getElementsByTagName('id')->item(0)->nodeValue;
                                    $videoId = $curVideo->getElementsByTagName('video_id')->item(0)->nodeValue;
                                    $title = $curVideo->getElementsByTagName('title')->item(0)->nodeValue;
                                    
                                    //if(!isset($arrSproutVideoIds[$videoId]))
                                        //$arrSproutVideoIds[$videoId] = $title;
                                    
                                    $description = $curVideo->getElementsByTagName('description')->item(0)->nodeValue;
                                    $series = explode(',', $curVideo->getElementsByTagName('series')->item(0)->nodeValue);
                                    $embedCode = $curVideo->getElementsByTagName('embed_code')->item(0)->nodeValue;

                                    if($_POST['upload_as'] == '1' || $_POST['upload_as'] == '3')
                                    {
                                        /* Import as posts */
                                        $arrPost = array(
                                            'post_title' => $title,
                                            'post_content' => ($embedCode!= '' ? '<p>'.$embedCode.'</p>' : '').($description!= '' ? '<p>'.$description.'</p>' : ''),
                                            'post_status' => 'publish'
                                        );
                                        
                                        
                                        /* Check if post exists */
                                        $args = array(
                                            'meta_query' => array(
                                                array(
                                                    'key' => 'export_video_id',
                                                    'value' => $id
                                                )
                                            )
                                        );
                                        
                                        
                                        if($_POST['upload_as'] == '3')
                                        {
                                            $arrPost['post_type'] = 'page';
                                            $args['post_type'] = 'page';
                                        }
                                        
                                        $arrEditPost = get_posts($args);
                                        
                                        if(is_array($arrEditPost) && count($arrEditPost) > 0 && isset($arrEditPost[0]) && isset($arrEditPost[0]->ID))
                                            $arrPost['ID'] = $arrEditPost[0]->ID;
                                        
                                        $postId = wp_insert_post($arrPost);
                                        
                                        if($_POST['upload_as'] == '1')
                                        {
                                            $arrTerms = array();
                                            if(is_array($series) && count($series) > 0)
                                            {
                                                foreach($series as $curSeries)
                                                {
                                                    $curSeries = trim($curSeries);
                                                    $term = term_exists($curSeries,'category');
                                                    
                                                    if($term == 0 || $term == null)
                                                        $term = wp_insert_term($curSeries,'category');
                                                    
                                                    if(isset($term['term_id']))
                                                        $arrTerms[] = $term['term_id'];
                                                }
                                            }
                                            
                                            if(is_array($arrTerms) && count($arrTerms) > 0)
                                            {
                                                wp_set_post_categories( $postId, $arrTerms);
                                            }
                                        }
                                        update_post_meta($postId, 'export_video_id',$id);
                                    }
                                    else if($_POST['upload_as'] == '2')
                                    {
                                        $content = '<div class="video-container">'.($title != '' ? '<h2>'.$title.'</h2>' : '').($embedCode != '' ? '<p>'.$embedCode.'</p>' : '').($description != '' ? '<p>'.$description.'</p>' : '').'</div>';

                                        /* Import as page */
                                        if(is_array($series) && count($series) > 0)
                                        {
                                            foreach($series as $curSeries)
                                                $arrPagesData[trim($curSeries)][] = $content;
                                        }
                                    }
                                }
                                
                                if(!$isPost)
                                {
                                    
                                    if(is_array($arrPagesData) && count($arrPagesData) > 0)
                                    {
                                        foreach($arrPagesData as $pageTitle => $pageContent)
                                        {
                                            $arrPage = array(
                                                'post_title' => $pageTitle,
                                                'post_content' => implode('',$pageContent),
                                                'post_status' => 'publish',
                                                'post_type' => 'page'
                                            );
                                            
                                            /* Check if post exists */
                                            $args = array(
                                                'post_type' => 'page',
                                                'meta_query' => array(
                                                    array(
                                                        'key' => 'page_series',
                                                        'value' => $pageTitle
                                                    )
                                                )
                                            );
                                            $arrEditPages = get_posts($args);

                                            if(is_array($arrEditPages) && count($arrEditPages) > 0 && isset($arrEditPages[0]) && isset($arrEditPages[0]->ID))
                                                $arrPage['ID'] = $arrEditPages[0]->ID;
                                            
                                            $pageId = wp_insert_post($arrPage);

                                            update_post_meta($pageId,'page_series',$pageTitle);
                                        }
                                    }
                                }

                                
                                /* Update videos */
                                //update_option('sproutvideoids',$arrSproutVideoIds);
                                
                                wp_redirect(admin_url('admin.php?page=upload_video') . '&i=success');
                                exit;
                            }
                            else
                            {
                                wp_redirect(admin_url('admin.php?page=upload_video') . '&i=novid');
                                exit;
                            }
                        }
                        else
                        {
                            wp_redirect(admin_url('admin.php?page=upload_video') . '&i=invalidxml');
                            exit;
                        }
                    }
                    else
                    {
                        wp_redirect(admin_url('admin.php?page=upload_video') . '&i=invalidimportto');
                        exit;
                    }
                }
                else
                {
                    wp_redirect(admin_url('admin.php?page=upload_video') . '&i=invalidfiletype');
                    exit;
                }
            }
            else
            {
                wp_redirect(admin_url('admin.php?page=upload_video') . '&i=nofile');
                exit;
            }
        }
    }
    
    function import_vide_wp_head_hook()
    {
?>
        <style>
            .video-container{
                margin: 0 0 50px 0;
            }
        </style>
<?php
    }
    