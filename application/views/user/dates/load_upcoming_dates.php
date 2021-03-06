
<?php if(isset($intros_data['upcoming']) && $intros_data['upcoming']):?>
<?php foreach ($intros_data['upcoming'] as $intro):?>
<!-- Start USER DIV  -->
<div class="itemBox divider"
	id="intro_<?php echo $intro['user_intro_id']?>">
	<?php include APPPATH.'views/user/include/action_box.php'?>

	<div class="userBox bor-none">
		<div class="userBox-wrap">
			<div class="userTopRow">
				<div class="userTopRowHed">
				<?php echo $intro['intro_name']?>
				</div>
				<div class="userTopRowTxt">
				<?php echo translate_phrase('Introduced on  ').date(DATE_FORMATE,strtotime($intro['intro_available_time']))?>
					<span class="Red-color">(<?php echo date("Y-m-d",strtotime($intro['intro_expiry_time'])) < SQL_DATE?translate_phrase('expired on '):translate_phrase('expires on '); echo date(DATE_FORMATE,strtotime($intro['intro_expiry_time']));?>)</span>
				</div>
			</div>
			<div class="userTop">
				<div class="userBoxLeft">
					<!-- -START PHOTO SECTION -->
				<?php if($intro['privacy_photos'] == 'SHOW') :?>
				<?php if (isset($intro['primary_photo'])&& $intro['primary_photo']): ?>
					<div class="img-left-box">
						<ul class="slider">
							<li class="img-slide"><a
								href="<?php echo $intro['view_profile_link']?>"><img
									style="height: 205px; max-width: 205px;"
									src="<?php echo $intro['primary_photo'] ?>" /> </a>
							</li>
						</ul>
					</div>
					<?php else:?>
					<?php echo translate_phrase('No photos added yet');?>
					<?php endif;?>

					<?php if (isset($intro['other_photos'])&& $intro['other_photos']): ?>
					<div class="sml-img">
						<div class="view4more-link">
							<a href="<?php echo $intro['view_profile_link']?>"> <?php echo translate_phrase('View ').$intro['other_photos'].translate_phrase(' more '); echo $intro['other_photos'] > 1?translate_phrase('photos'):translate_phrase('photo'); echo translate_phrase(' in profile');?>
							</a>
						</div>
					</div>
					<?php endif;?>
					<?php else:?>
					<?php echo translate_phrase('Photos available upon request');?>
					<?php endif;?>
					<!-- -END PHOTO SECTION -->
					<div class="profileBtn">
						<a href="<?php echo $intro['view_profile_link']?>"><div
								class="appr-cen">
								<?php echo translate_phrase('View Profile')?>
							</div> </a>
					</div>
				</div>

				<div class="userBoxRight">
					<div class="userbox-innr">
						<div class="userbox-left-txt">
						<?php echo translate_phrase('Compatibility Score')?>
							:
						</div>
						<div
							class="score-txt <?php echo $intro['intro_score']['score'] < 50 ?'Red-color':'';?>">
							<?php echo round($intro['intro_score']['score']).'/100'?>
						</div>
					</div>
					<div class="detail-list">
						<ul>
						<?php echo (isset($intro['intro_age']) && $intro['intro_age'])?'<li>'.$intro['intro_age'].translate_phrase(' years old').'</li>':''?>
						<?php echo (isset($intro['intro_ethnicity']) && $intro['intro_ethnicity'])?'<li>'.$intro['intro_ethnicity'].'</li>':''?>
							<li><?php echo (isset($intro['sexual_status']) && $intro['sexual_status'])?$intro['sexual_status']:''?>
							</li>
							<?php echo (isset($intro['intro_height']) && $intro['intro_height'])?'<li>'.$intro['intro_height'].'</li>':''?>
							<?php echo (isset($intro['intro_body_type']) && $intro['intro_body_type'])?'<li>'.$intro['intro_body_type'].'</li>':''?>
							<?php echo (isset($intro['zodiac_sign']) && $intro['zodiac_sign'])?'<li>'.$intro['zodiac_sign'].'</li>':''?>
						</ul>
					</div>

					<?php if(isset($intro['intro_current_location'])):?>
					<div class="userbox-innr">
						<div class="userbox-left-txt">
						<?php echo translate_phrase('Lives in');?>
						</div>
						<div class="userbox-right-txt">
						<?php echo $intro['intro_current_location']['city_description'].', '.$intro['intro_current_location']['country_description']?>
						</div>
					</div>
					<?php endif;?>

					<?php if(isset($intro['intro_study']) && $intro['intro_study']):?>
					<div class="userbox-innr">
						<div class="userbox-left-txt">
						<?php echo translate_phrase('Studied at');?>
						</div>
						<div class="userbox-right-txt">
						<?php echo $intro['intro_study'];?>
						</div>
					</div>
					<?php endif;?>


					<?php if(isset($intro['intro_works']) && $intro['intro_works']):?>
					<div class="userbox-innr">
						<div class="userbox-left-txt">
						<?php echo translate_phrase('Works as');?>
						</div>
						<div class="userbox-right-txt">
						<?php echo $intro['intro_works'];?>
						</div>
					</div>
					<?php endif;?>

					<div class="appear-prt comn-top-mar">
					<?php if(isset($intro['common_likes']) && $intro['common_likes']):?>
						<h2>
						<?php echo translate_phrase('Common Interests');?>
						</h2>
						<div class="appear-prt-but">
						<?php foreach ($intro['common_likes'] as $interest):?>
							<div class="appr-cen Upgrd-blue">
							<?php echo $interest;?>
							</div>
							<?php endforeach;?>
						</div>
						<?php endif;?>

						<?php if(user_country_id() != FB_RESTRICTED_COUNTRY):?>
						<div class="mutualLink">
						<?php if(isset($intro['fb_mutual_friend']) && $intro['fb_mutual_friend']):?>
							<a
								href="<?php echo base_url().url_city_name().'/mutual-friends.html?return_to=my-date.html&tab=upcoming&fb_id='.$intro['fb_id'];?>"><?php echo $intro['fb_mutual_friend'];?>
							</a>
							<?php else:?>
							<span><?php echo $user_data['facebook_id'] && $intro['facebook_id']?translate_phrase('No Mutual Friends'):translate_phrase('Mutual Friends Info Not Available');?>
							</span>
							<?php endif;?>
						</div>
						<?php endif;?>

					</div>
				</div>
			</div>
			<?php include APPPATH.'views/user/include/chat_box.php'?>
		</div>
	</div>

</div>
<!-- END USER DIV  -->
							<?php endforeach;?>
							<?php endif;?>