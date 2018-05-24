<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
    <title>My app</title>
	<link type="text/css" href="./css/bootstrap.min.css" rel="stylesheet">
	<link type="text/css" href="./css/b24app.css" rel="stylesheet">
	<link type="text/css" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css" rel="stylesheet">
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
	<script type="text/javascript" src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.11.1/i18n/jquery-ui-i18n.min.js"></script>
	<script type="text/javascript" src="./js/bootstrap.min.js"></script>
	<script type="text/javascript">
		var bitrixDomain = '<?=$this->domain;?>';
		$(function() {
			var curr_date = new Date();
			var first_day = new Date(curr_date.getFullYear(), curr_date.getMonth());
			$.datepicker.setDefaults( $.datepicker.regional["ru"] );
			$('#opt-date-from, #opt-date-to').datepicker({
					maxDate: curr_date,
			});
			/*$('#opt-date-from').datepicker('setDate', first_day);
			$('#opt-date-to').datepicker('setDate', curr_date);*/

			var selectorTitle = ['Выбрать','Сменить'];
			
			function empty(mixedValue) {
				return (mixedValue === undefined ||
					mixedValue === null || mixedValue === ""
					|| mixedValue === "0" || mixedValue === 0
					|| mixedValue === false || (Array.isArray(mixedValue)
					&& mixedValue.length == 0));
			}
			
			function setTitle() {
				$('#opt-project-sel').html($('#opt-project-item').val() === "0" ? selectorTitle[0] : selectorTitle[1]);
			}
			
			function requireFields() {
				var fields_counter = 0;

				$('.form-control').each(function(indx){
					if ($(this).attr('data-element-type') == 'ajax-param') {
						if (!empty($(this).val())) {
							fields_counter += 1;
							return false;
						}
					}
				});
				
				if (fields_counter > 0) {
					$('#submit-button').removeAttr('disabled');
				} else {
					$('#submit-button').attr('disabled', true);
				}
			}
			
			setTitle();
			requireFields();
			
			$('#opt-project-sel').popover({
				container: 'body',
				placement: 'bottom',
				trigger: 'manual',
				html: true,
				content: function() {
					return $('#opt-project-sel-popover').html();
				}
			});
			
			$('#opt-project-sel').click(function(){
				if (!$('div').is('.popover')) {
					$(this).popover('toggle');
				}
			});
			
			$(document).on('click', '.opt-project-item', function(){
				$('#opt-project-item').val($(this).attr('data-opt-project-item-id')).trigger('change');
				$('#opt-project-item-name').html($(this).html());
				$('#opt-project-sel').popover('toggle');
				$('#opt-project-item-name-container').show();
				setTitle();
			});
			
			$(document).on('click', '#opt-project-item-name-close', function(){
				$('#opt-project-item').val('0').trigger('change');
				$('#opt-project-item-name').html('');
				$('#opt-project-item-name-container').hide();
				setTitle();
			});
			
			$('.form-control').change(function(){
				if ($(this).attr('data-element-type') == 'ajax-param') {
					requireFields();
				}
			});
			
			$('#submit-button').click(function(){
				var post_data = {ajax_operation : 'test'};
				post_data['DOMAIN'] = bitrixDomain;
				$('.form-control').each(function(){
					if ($(this).attr('data-element-type') == 'ajax-param' && !empty($(this).val())) {
						post_data[$(this).attr('name')] = $(this).val();
					}
				});
				
				$.ajax({
					url: 'b24app.php',
					type: 'POST',
					//processData: false,
					//contentType: false,
					data: $.param(post_data),
					dataType: 'html',
					success: function (result) {
						$('#answer-content').html(result);
					},
					
					error: function (xhr, ajaxOptions, thrownError) {
						//var message = thrownError + "\r\n" + xhr.statusText + "\r\n" + xhr.responseText;
						var message = 'Произошла ошибка отправки запроса. Обратитесь к менеджеру.';
						errorShow(message);
					}
				});
			});
		});
	</script>
</head>
<body>
	<div class="options-container well">
		<p>Срок действия авторизации:&nbsp;<?=date('d.m.Y H:i:s', $this->expire);?></p>
		<form class="form-horizontal">
			<div class="form-group">
				<label class="control-label col-sm-1 project-form-field">Проект</label>
				<div class="col-sm-6">
					<div class="form-control project-form-field">
						<div class="project-form-field-item hide list-group" id="opt-project-sel-popover">
						<?php foreach($bitrix_result['result'] as $result_item) { ?>
							<button type="button" class="opt-project-item list-group-item" data-opt-project-item-id="<?=$result_item['ID'];?>"><?=$result_item['NAME'];?></button>
						<?php } ?>
						</div>
						<span class="project-form-field-item" id="opt-project-item-name-container">
							<span class="project-form-field-item-element" id="opt-project-item-name"></span><!--
							--><span class="project-form-field-item-element" id="opt-project-item-name-close">
								<span class="glyphicon glyphicon-remove" aria-hidden="true"></span>
							</span>
						</span>
						<span class="project-form-field-item">
							<a tabindex="0" class="popover-toggle" id="opt-project-sel" type="button" role="button" data-toggle="popover" data-html="true"></a>
						</span>
						<input type="hidden" id="opt-project-item" name="opt-project-item" class="form-control" data-element-type="ajax-param" value="0">
					</div>
				</div>
			</div>
			<div class="form-group">
				<label class="control-label col-sm-1">Период</label>
				<div class="col-sm-2">
					<input type="text" class="form-control" id="opt-date-from" name="opt-date-from" data-element-type="ajax-param" value="">
				</div>
				<div class="col-sm-2">
					<input type="text" class="form-control" id="opt-date-to" name="opt-date-to" data-element-type="ajax-param" value="">
				</div>
			</div>
			<div class="form-group">
				<div class="col-sm-offset-1 col-sm-10">
					<button type="button" class="btn btn-default" id="submit-button">Sign in</button>
				</div>
			</div>
		</form>
	</div>

	<div class="answer-container">
		
	<?php
		if (empty($bitrix_error)) { ?>
			<div id="answer-content"></div>
		<?php 	
		} else { ?>
			<div class="alert alert-danger" role="alert"><?=$bitrix_error;?></div>
		<?php
		}
    ?>
		
	</div>
</body>
</html>