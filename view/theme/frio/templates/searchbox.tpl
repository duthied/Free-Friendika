
<div id="{{$id}}" {{* class="input-group" *}}>
	<div id="search-wrapper">
		<form action="{{$action_url}}" method="get" >
			<div class="row">
				<div class="col-md-2"></div>
				<div class="col-md-8 ">

					<div class="form-group form-group-search">
						<input type="text" name="search" id="search-text" class="search-input form-control form-search" data-toggle="tooltip" title="{{$search_hint}}" placeholder="{{$search_label}}" value="{{$s}}" />
						<button id="search-submit" class="btn btn-default btn-sm form-button-search" type="submit" name="submit" value="{{$search_label}}">{{$search_label}}</button>
					</div>

					<div class="col-md-4"></div>
					<div class="col-md-8">
						{{* The button to save searches *}}
						{{if $savedsearch}}
						<button class="btn btn-primary btn-small pull-right" type="submit" name="save" id="search-save" value="{{$save_label}}">{{$save_label}}</button>
						{{/if}}

						{{* The select popup menu to select what kind of results the user would like to search for *}}
						{{if $searchoption}}
						<div class="col-md-6 pull-right">
							<div class="form-group field select">
								<select name="search-option" id="search-options" class="form-control form-control-sm">
									<option value="fulltext">{{$searchoption.0}}</option>
									<option value="tags">{{$searchoption.1}}</option>
									<option value="contacts">{{$searchoption.2}}</option>
									{{if $searchoption.3}}<option value="forums">{{$searchoption.3}}</option>{{/if}}
								</select>

							</div>
						</div>
						{{/if}}
						
					</div>
				</div>
				<div class="col-md-2"></div>

				<div class="clearfix"></div>

			</div>
			
		</form>
	</div>
</div>
