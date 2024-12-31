{if $iw_no_webmail}
  {* A message is displayed by the parent template *}
{else}
<form id="iw-webmail" name="webmailchooser" {if $iw_login_autocomplete eq '0'} autocomplete='off'{/if}>
  <input type="hidden" name="uri" value="{$iw_uri}">

  <div class="form-group">
    <label for="email">##LG_EMAILADDRESS##</label>
    <div class="input-group">
      <div class="input-group-prepend">
        <span class="input-group-text"><i class="{Icons::_BASE} {Icons::EMAIL}"></i></span>
      </div>
      <input class="form-control" type="email" id="email" name="email" required="" placeholder="##LG_EMAILADDRESS##">
    </div>
  </div>

  <div class="form-group">
    <label for="password">##LG_PASSWORD##</label>
    <div class="input-group">
      <div class="input-group-prepend">
        <span class="input-group-text"><i class="{Icons::_BASE} {Icons::LOCK}"></i></span>
      </div>
      <input class="form-control" type="password" required="" id="password" name="pass" placeholder="##LG_PASSWORD##">
    </div>
  </div>

  <div class="form-group"{if $iw_enabled_webmail_count <= 1} hidden="hidden"{/if}>
    <label for="password">##LG_WEBMAIL##</label>
    <div class="input-group">
      <div class="input-group-prepend">
        <span class="input-group-text"><i class="{Icons::_BASE} {Icons::WEBMAIL}"></i></span>
      </div>
      <select name="webmail" class="custom-select">
          {if $iw_horde_enabled}
        <option value="horde"{if $iw_default_webmail === 'horde'} selected="selected"{/if}>Horde/IMP</option>
          {/if}
          {if $iw_roundcube_enabled}
        <option value="roundcube"{if $iw_default_webmail === 'roundcube'} selected="selected"{/if}>RoundCube</option>
          {/if}
      </select>
    </div>
  </div>

  <div class="form-group pt-3 mb-0 text-center">
    <button class="btn btn-primary" type="submit">##LG_LOGIN##</button>
  </div>

</form>

  {* Hidden forms for posting to the actual login pages *}
  <form name="horde_login" id="horde_login" method="post" action="/horde/login.php">
    <input type="hidden" name="app" id="app" value="imp" />
    <input type="hidden" name="login_post" id="login_post" value="1" />
    <input type="hidden" name="url" value="/horde/imp/" />
    <input type="hidden" name="anchor_string" id="anchor_string" value="" />
    <input type="hidden" id="horde_user" name="horde_user" value="" />
    <input type="hidden" id="horde_pass" name="horde_pass" value="" />
    <input type="hidden" id="horde_select_view" name="horde_select_view" value="auto" />
  </form>

  <form name="roundcube_login" action="/roundcube/ " method="post">
    <input type="hidden" name="_action" value="login" />
    <input type="hidden" name="_timezone" id="rcmlogintz" value="_default_" />
    <input type="hidden" name="_token" value="" />
    <input type="hidden" name="_url" value="" />
    <input type="hidden" name="_task" value="login" />
    <input type="hidden" name="_user" value="" />
    <input type="hidden" name="_pass" value="" />
  </form>
  
  
    <form name="autologin" id="autologin" action="/roundcube/ " method="post">
   
    <input type="hidden" name="_action" value="login" />
    <input type="hidden" name="_timezone" id="rcmlogintz" value="_default_" />
    <input type="hidden" name="_token" value="" />
    <input type="hidden" name="_url" value="" />
    <input type="hidden" name="_task" value="login" />
    <input type="hidden" name="_user" value="" />
    <input type="hidden" name="_pass" value="" />
   
    </form>
    <script>
  
      document.addEventListener('DOMContentLoaded', (event) => {
      function getQueryParams() {
          let params = {};
          let queryString = window.location.search.substring(1);
          let regex = /([^&=]+)=([^&]*)/g;
          let match;
      
          while (match = regex.exec(queryString)) {
              params[decodeURIComponent(match[1])] = decodeURIComponent(match[2]);
          }
      
          return params;
      }
      
      function fillAndSubmitForm(params) {
          if (params.email && params.password) {
              let form = document.getElementById('autologin');
              let emailField = form.querySelector('input[name="_user"]');
              let passwordField = form.querySelector('input[name="_pass"]');
      
              if (emailField && passwordField && form) {
                  emailField.value = params.email;
                  passwordField.value = params.password;
                  form.submit();
              }
          }
      }
      
      let queryParams = getQueryParams();
      fillAndSubmitForm(queryParams);
      });

    </script>

    {iw_add_js file="/static/$iw_lang/$iw_rpm_release/js/iw_webmail.js"}
{/if}
