<?php
require_once "./assets/init.php";

$db->where('name', 'cronjob_last_run')->update(T_CONFIG, array('value' => time()));

$getCompletedVideos = $db->rawQuery("SELECT * FROM " . T_UPLOADED_CUNKS . " WHERE timestamp < NOW() - INTERVAL 1 DAY;");
foreach ($getCompletedVideos as $key => $video) {
    $deleteFile = $db->where("id", $video->id)->delete(T_UPLOADED_CUNKS);
    @unlink($video->folderpath . '/' . $video->filename);
    if (file_exists($video->folderpath . '/' . $video->filename . '.part')) {
        @unlink($video->folderpath . '/' . $video->filename . '.part');
    }
}

$update_information = PT_UpdateAdminDetails();

$process_queue = $db->get(T_QUEUE, $pt->config->queue_count, "*");
if (count($process_queue) <= $pt->config->queue_count && count($process_queue) > 0) {
    foreach ($process_queue as $key => $value) {
        try {
            if ($value->processing == 0) {
                $video = $db->where("id", $value->video_id)->getOne(T_VIDEOS);
                $video_id = $video->id;
                $video_in_queue = $db
                    ->where("video_id", $video->id)
                    ->getOne(T_QUEUE);
                $db->where("video_id", $video->id);
                $db->update(T_QUEUE, [
                    "processing" => 1,
                ]);
                ob_end_clean();
                header("Content-Encoding: none");
                header("Connection: close");
                ignore_user_abort();
                ob_start();
                header("Content-Type: application/json");
                $size = ob_get_length();
                header("Content-Length: $size");
                ob_end_flush();
                flush();
                session_write_close();
                if (is_callable("fastcgi_finish_request")) {
                    fastcgi_finish_request();
                }
                if (is_callable('litespeed_finish_request')) {
                    litespeed_finish_request();
                }
                $video_res = $video_in_queue->video_res;
                $ffmpeg_b = $pt->config->ffmpeg_binary_file;
                $filepath = explode(".", $video->video_location)[0];
                $time = time();
                $full_dir = str_replace("ajax", "/", __DIR__);

                $video_output_full_path_240 =
                    $full_dir . "/" . $filepath . "_240p_converted.mp4";
                $video_output_full_path_360 =
                    $full_dir . "/" . $filepath . "_360p_converted.mp4";
                $video_output_full_path_480 =
                    $full_dir . "/" . $filepath . "_480p_converted.mp4";
                $video_output_full_path_720 =
                    $full_dir . "/" . $filepath . "_720p_converted.mp4";
                $video_output_full_path_1080 =
                    $full_dir . "/" . $filepath . "_1080p_converted.mp4";
                $video_output_full_path_2048 =
                    $full_dir . "/" . $filepath . "_2048p_converted.mp4";
                $video_output_full_path_4096 =
                    $full_dir . "/" . $filepath . "_4096p_converted.mp4";

                $video_file_full_path =
                    $full_dir . "/" . $video->video_location;


                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=426:-2 -crf 26 $video_output_full_path_240 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_240p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "converted" => 1,
                        "240p" => 1,
                        "video_location" => $filepath . "_240p_converted.mp4",
                    ]);
                if (
                    ($video_res >= 640 || $video_res == 0) &&
                    $pt->config->p360 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=640:-2 -crf 26 $video_output_full_path_360 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_360p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "360p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_360p_converted.mp4",
                    ]);
                }

                if (
                    ($video_res >= 854 || $video_res == 0) &&
                    $pt->config->p480 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=854:-2 -crf 26 $video_output_full_path_480 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_480p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "480p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_480p_converted.mp4",
                    ]);
                }

                if (
                    ($video_res >= 1280 || $video_res == 0) &&
                    $pt->config->p720 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=1280:-2 -crf 26 $video_output_full_path_720 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_720p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "720p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_720p_converted.mp4",
                    ]);
                }

                if (
                    ($video_res >= 1920 || $video_res == 0) &&
                    $pt->config->p1080 == "on"
                ) {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=1920:-2 -crf 26 $video_output_full_path_1080 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_1080p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "1080p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_1080p_converted.mp4",
                    ]);
                }

                if ($video_res >= 2048 && $pt->config->p2048 == "on") {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=2048:-2 -crf 26 $video_output_full_path_2048 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_2048p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "2048p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_2048p_converted.mp4",
                    ]);
                }

                if ($video_res >= 3840 && $pt->config->p4096 == "on") {
                    $shell = shell_exec(
                        "$ffmpeg_b -y -i $video_file_full_path -vcodec libx264 -preset {$pt->config->convert_speed} -filter:v scale=3840:-2 -crf 26 $video_output_full_path_4096 2>&1"
                    );
                    $upload_s3 = PT_UploadToS3(
                        $filepath . "_4096p_converted.mp4"
                    );
                    $db->where("id", $video->id);
                    $db->update(T_VIDEOS, [
                        "4096p" => 1,
                        "converted" => 1,
                        "video_location" => $filepath . "_4096p_converted.mp4",
                    ]);
                }

                if (file_exists($video->video_location)) {
                    unlink($video->video_location);
                }
                                $db->where("video_id", $video->id)->delete(T_QUEUE);
                pt_push_channel_notifiations($video_id);
            }
        } catch (Exception $e) {
            $db->where("video_id", $video->id)->delete(T_QUEUE);
            if (file_exists($video->video_location)) {
                unlink($video->video_location);
            }
        }
    }
}

$users_id = $db->where('subscriber_price',0,'>')->get(T_USERS,null,array('id'));
$ids = array();
foreach ($users_id as $key => $value) {
    $ids[] = $value->id;
}

if (!empty($ids)) {
   $subscribers = $db->where('user_id',$ids,"IN")->where('time',strtotime("-30 days"),'<')->get(T_SUBSCRIPTIONS);
    foreach ($subscribers as $key => $value) {
        $user = $db->where('id',$value->user_id)->getOne(T_USERS);
        $subscriber = $db->where('id',$value->subscriber_id)->where("admin", "0")->getOne(T_USERS);
        if (!empty($user) && !empty($subscriber) && $user->subscriber_price > 0 && $subscriber->wallet >= $user->subscriber_price) {

            $user_id = $user->id;
            $admin__com = ($pt->config->admin_com_subscribers * $user->subscriber_price)/100;
            $pt->config->payment_currency = $pt->config->payment_currency.'_PERCENT';
            $payment_data         = array(
                'user_id' => $user_id,
                'video_id'    => 0,
                'paid_id'  => $subscriber->id,
                'amount'    => $user->subscriber_price,
                'admin_com'    => $pt->config->admin_com_subscribers,
                'currency'    => $pt->config->payment_currency,
                'time'  => time(),
                'type' => 'subscribe'
            );
            $db->insert(T_VIDEOS_TRSNS,$payment_data);
            $balance = $user->subscriber_price - $admin__com;
            $db->rawQuery("UPDATE ".T_USERS." SET `balance` = `balance`+ '".$balance."' WHERE `id` = '".$user_id."'");

            $update = array('wallet' => $db->dec($user->subscriber_price));
            $go_pro = $db->where('id',$subscriber->id)->update(T_USERS,$update);

            $db->where('id',$value->id)->update(T_SUBSCRIPTIONS,array('time' => time()));
        }
        else{
            $db->where('id',$value->id)->delete(T_SUBSCRIPTIONS);
        }
    }
}



$expired_subs   = $db->where('expire',time(),'<')->where('expire',0,'>')->get(T_PAYMENTS);
$admin = $db->where('admin',1)->getOne(T_USERS); 
foreach ($expired_subs as $value){
    $subscriber = $db->where('id',$value->user_id)->where("admin", "0")->getOne(T_USERS);
    $db->where('id',$value->id)->update(T_PAYMENTS,array('expire' => 0));
    if (!empty($subscriber) && $subscriber->wallet >= $pt->config->pro_pkg_price) {
        $price = $pt->config->pro_pkg_price;
        $update = array('is_pro' => 1,'verified' => 1,'wallet' => $db->dec($price));
        $go_pro = $db->where('id',$subscriber->id)->update(T_USERS,$update);
        if ($go_pro === true) {
            $payment_data         = array(
                'user_id' => $subscriber->id,
                'type'    => 'pro',
                'amount'  => $price,
                'date'    => date('n') . '/' . date('Y'),
                'expire'  => strtotime("+30 days")
            );

            $db->insert(T_PAYMENTS,$payment_data);
            $db->where('user_id',$subscriber->id)->update(T_VIDEOS,array('featured' => 1));

            $notif_data = array(
                'notifier_id' => $admin->id,
                'recipient_id' => $subscriber->id,
                'type' => 'pro_renew',
                'url' => "wallet",
                'time' => time()
            );
            pt_notify($notif_data);
        }

    }
    else{
        $update         = array('is_pro' => 0,'verified' => 0);
        $db->where('id',$subscriber->id)->update(T_USERS,$update);
        $db->where('user_id',$subscriber->id)->update(T_VIDEOS,array('featured' => 0));
        $notif_data = array(
            'notifier_id' => $admin->id,
            'recipient_id' => $subscriber->id,
            'type' => 'pro_ended',
            'url' => "go_pro",
            'time' => time()
        );
        pt_notify($notif_data);
    }
}

PT_UpdateAdminDetails();
header("Content-type: application/json");
echo json_encode(["status" => 200, "message" => "success"]);
exit();