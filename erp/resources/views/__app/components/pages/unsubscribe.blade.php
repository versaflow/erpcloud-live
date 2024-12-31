@extends( '__app.layouts.guest' )

@if(!request()->ajax())
	
@endif
@section('content')

 <form action="/mail_unsubscribe/{{$encoded_link}}" id="unsubscribe_form">
    <div class="unsubscribe">
        <div class="unsubscribe-container">
      
            <div class="unsubscribe-form">
              
                <p class="unsubscribe-form__header">Unsubscribe me from all marketing emails</p>
                <p class="unsubscribe-form__p">If you have a moment, please let us know why you unsubscribed:</p>
                <form action="/tracking/report" id="unsubscribe-form" method="post">
                    <div class="unsubcribe-form__single">
                        <input type="radio" id="noLonger" name="unsubscribereason" value="nolongerwant" checked="checked">
                        <label for="noLonger">I no longer wish to receive your emails</label>
                    </div>
                    <div class="unsubcribe-form__single">
                        <input type="radio" id="notRelevant" name="unsubscribereason" value="irrelevantcontent">
                        <label for="notRelevant">Content is not relevant or interesting</label>
                    </div>
                    <div class="unsubcribe-form__single">
                        <input type="radio" id="receiveThese" name="unsubscribereason" value="toofrequent">
                        <label for="receiveThese">You are sending too frequently</label>
                    </div>
                    <div id="other-textarea" class="unsubcribe-form__single unsubcribe-form__textarea">
                        <textarea maxlength=500 cols=40 name="unsubscribereasonnotes" placeholder="Optionally provide more information" rows="4"></textarea>
                    </div>
                    <div class="unsubcribe-form__single unsubscribe-form_submit-button">
                        <button id="unsubcribeInfoSubmit" class="unsubcribe-form-btn__regular" type="submit">Unsubscribe</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</form>

@endsection

@push('page-scripts')

<script type="text/javascript">

$(document).ready(function() {

$('#unsubscribe_form').on('submit', function(e) {
 	e.preventDefault();
    formSubmit("unsubscribe_form");
});
});
</script>
@endpush

@push('page-styles')

 <style>

        @import url(https://fonts.googleapis.com/css?family=Muli:400,600,700&display=swap);
        @import url(https://fonts.googleapis.com/css?family=Nunito:400,600,700&display=swap);
        
        #page-wrapper{
        box-shadow: none !important;
        }
        .unsubscribe {
            display: grid;
            grid-template-rows: 1fr auto;
            grid-row-gap: 0;
            justify-content: center;
            height: 100vh
        }

        .unsubscribe-RTL {
            direction: rtl
        }

        .align-RTL {
            text-align: right!important
        }

        .or-RTL {
            margin-right: 229px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single.form-RTL {
            display: block!important
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]+label,
        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single.form-RTL input[type=radio]+label {
            padding-right: 30px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single.form-RTL input[type=radio]+label {
            display: block;
            background: url(https://elasticemail.com/files/unsubscribe/radio/radio_unchecked_dark.svg) center right no-repeat;
            max-height: 27px;
            text-align: right
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single.form-RTL input[type=radio]:checked+label {
            background: url(https://elasticemail.com/files/unsubscribe/radio/radio_checked_dark.svg) center right no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single.form-RTL input[type=radio]:hover {
            background: url(https://elasticemail.com/files/unsubscribe/radio/Radio_unchecked-focus.svg) center right no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single.form-RTL input[type=radio]:checked:hover {
            background: url(https://elasticemail.com/files/unsubscribe/radio/radio_checked-focus.svg) center right no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container {
            display: grid;
            grid-template-rows: auto;
            grid-row-gap: 0
        }

        @media only screen and (min-width:769px) {
            .unsubscribe .unsubscribe-container {
                width: 570px;
                padding: 32px
            }
        }

        @media only screen and (max-width:768px) {
            .unsubscribe .unsubscribe-container {
                width: auto;
                padding: 24px
            }
        }

        .unsubscribe .unsubscribe-container .unsubscribe-logo {
            display: grid;
            align-items: center;
            padding: 32px 0;
            justify-content: center;
        }

        .unsubscribe .unsubscribe-container .unsubscribe-logo .logoimage {
            width: 100%;
        }

        .unsubscribe .unsubscribe-container .unsubscribe-info {
            display: grid;
            align-content: center;
            justify-content: center;
        }

        @media only screen and (min-width:769px) {
            .unsubscribe .unsubscribe-container .unsubscribe-info {
                min-height: 108px
            }
        }

        @media only screen and (max-width:768px) {
            .unsubscribe .unsubscribe-container .unsubscribe-info {
                min-height: 128px
            }
        }

        .unsubscribe .unsubscribe-container .unsubscribe-info .unsubscribe-info__successful {
            font-family: Nunito, sans-serif;
            font-size: 18px;
            font-weight: 700;
            font-stretch: normal;
            font-style: normal;
            line-height: 1.56;
            letter-spacing: normal;
            text-align: left;
            color: #32325c;
            margin: 0;
            padding: 6px 0
        }

        .unsubscribe .unsubscribe-container .unsubscribe-info .unsubscribe-info__description {
            font-family: Muli, sans-serif;
            font-size: 16px;
            font-weight: 400;
            font-stretch: normal;
            font-style: normal;
            line-height: 1.5;
            letter-spacing: normal;
            text-align: left;
            color: #677389;
            margin: 0;
            padding: 6px 0
        }

        .unsubcribe-form__single.unsubscribe-form_submit-button {
            padding: 0!important
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form {
            display: grid;
            align-content: center;
            border-radius: 6px;
            border: solid 1px #e2e8f0;
            background-color: #fff;
            max-width: 570px;
            padding: 32px;
            margin-bottom: 44px
        }

        .unsubscribe-button-box {
            margin-top: 10px
        }

        .unsubscribe-tracking {
            padding-top: 32px!important
        }

        .unsubscribe__or {
            width: 48px;
            height: 48px;
            border-radius: 26px;
            border: solid 1px #e2e8f0;
            background-color: #fff;
            margin-left: 229px;
            margin-top: -60px;
            margin-bottom: 24px;
            text-align: center;
            align-items: center;
            display: flex;
            justify-content: center;
            font-family: Nunito;
            font-size: 18px;
            font-weight: 700;
            line-height: 1.56;
            color: #32325c
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubscribe-form__header {
            font-family: Nunito, sans-serif;
            font-size: 18px;
            font-weight: 700;
            font-stretch: normal;
            font-style: normal;
            line-height: 1.56;
            letter-spacing: normal;
            text-align: left;
            margin-bottom: 8px;
            margin-top: 0;
            color: #32325c
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single {
            width: 100%;
            display: flex;
            font-family: Muli, sans-serif;
            padding: 10px 0
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single label {
            font-size: 14px;
            font-weight: 400;
            font-stretch: normal;
            font-style: normal;
            line-height: 1.43;
            letter-spacing: normal;
            text-align: left;
            color: #32325c
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single textarea {
            padding: 10px;
            font-family: Muli, sans-serif;
            border-radius: 4px;
            border: solid 1px #a0aec0;
            background-color: #fff;
            width: 100%;
            font-size: 16px;
            resize: none
        }

        textarea::placeholder {
            color: #a0aec0
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=radio] {
            display: none;
            visibility: hidden
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]+label,
        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=radio]+label {
            padding-left: 30px;
            word-break: break-word
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=radio]+label {
            display: inline-block;
            background: url(https://elasticemail.com/files/unsubscribe/radio/radio_unchecked_dark.svg) center left no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=radio]:checked+label {
            background: url(https://elasticemail.com/files/unsubscribe/radio/radio_checked_dark.svg) center left no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=radio]:hover {
            background: url(https://elasticemail.com/files/unsubscribe/radio/Radio_unchecked-focus.svg) center left no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=radio]:checked:hover {
            background: url(https://elasticemail.com/files/unsubscribe/radio/radio_checked-focus.svg) center left no-repeat;
            max-height: 27px
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__textarea {
            padding-bottom: 22px
        }

        .unsubscribe .unsubscribe-container .unsubcribe-form-btn__regular {
            cursor: pointer;
            border-radius: 4px;
            padding: 10px 24px;
            font-family: Nunito, sans-serif;
            font-size: 18px;
            font-weight: 400;
            font-stretch: normal;
            font-style: normal;
            text-align: center;
            line-height: 1.33;
            letter-spacing: normal;
            min-height: 44px;
            text-transform: none;
            border: none;
            background-color: #5457ff;
            color: #fff;
            min-width: 105px
        }

        .unsubscribe .unsubscribe-container .unsubcribe-form-btn__regular:hover {
            background: #3f42d9;
            color: #fff;
            text-decoration: none
        }

        .unsubscribe .unsubscribe-container .unsubcribe-form-btn__regular:focus {
            background: #3f42d9;
            border: 2px solid #32325c;
            padding: 8px 22px
        }

        .unsubscribe .unsubscribe-footer {
            display: grid;
            align-content: center;
            justify-content: center;
            text-align: center
        }

        @media only screen and (min-width:769px) {
            .unsubscribe .unsubscribe-footer {
                padding: 0 32px
            }
        }

        @media only screen and (max-width:768px) {
            .unsubscribe .unsubscribe-footer {
                padding: 0 24px
            }
        }

        .unsubscribe .unsubscribe-footer p {
            font-family: Muli, sans-serif;
            font-size: 14px;
            font-weight: 400;
            font-stretch: normal;
            font-style: normal;
            line-height: 1.43;
            letter-spacing: normal;
            color: #677389
        }

        .unsubscribe-form__p {
            font-family: Muli;
            font-size: 16px;
            line-height: 1.5;
            text-align: left;
            margin-top: 0;
            color: #677389
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox] {
            -webkit-appearance: none;
            height: 14px;
            width: 0;
            cursor: pointer;
            position: relative;
            border: none!important;
            transition: none;
            margin: 0
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]:before {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            border: none!important;
            text-align: center;
            color: #fff;
            transition: none;
            z-index: 2
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]:after {
            content: url(https://elasticemail.com/files/unsubscribe/checkbox/checkbox-default.svg);
            position: absolute;
            z-index: 1
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]:checked:before {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            text-align: center;
            border: none!important;
            color: #fff;
            content: url(https://elasticemail.com/files/unsubscribe/checkbox/checkbox-checked.svg);
            transition: none
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]:focus:before {
            border: none!important;
            content: url(https://elasticemail.com/files/unsubscribe/checkbox/checkbox-focus.svg);
            width: 100%;
            height: 100%
        }

        .unsubscribe .unsubscribe-container .unsubscribe-form .unsubcribe-form__single input[type=checkbox]:checked:focus:before {
            border: none!important;
            content: url(https://elasticemail.com/files/unsubscribe/checkbox/checkbox-focus-checked.svg);
            width: 100%;
            height: 100%
        }

        .unsubscribe-form__list-of-checkboxes {
            border-radius: 6px;
            border: solid 1px #e2e8f0;
            background-color: #f3f5ff;
            margin: 32px 0 0;
            padding: 20px 32px
        }

        .unsubscribe-button-without-tracking {
            margin-top: 22px
        }

        .confirmation {
            padding: 6px 32px!important
        }
    </style>
@endpush