function SWPWSShowAddressJSON( code,subdir,defaultText,paymentplanDefaultText )
{
  if($(code+":select_address") == null || $(code+":selected_address_container") == null)
	return;
  
  var url = subdir + "/index.php/sveawebpayws/base/selectAddressId";
  url += "?swpwsc=" + code + "&swpwssaid=" + $(code+":select_address").value;
  
  var executeGetPaymnetplan = false;
  var paymentplanInformationObject = $(code + ":paymentplans_information_container");
  if(paymentplanInformationObject != null)
	  executeGetPaymentplan = true;
  
  var updateObject = $(code+":selected_address_container");
  new Ajax.Request(url, {
    method:'get',
	requestHeaders: {Accept: 'application/json'},
	onSuccess: function(transport){
      // In here we do some stuff right.
	  var json = transport.responseText.evalJSON(true);
	  var success = json["success"];
	  var content = json["content"];
	  var status  = json["status"];

      if(success == false) {
        // If we couldn't find the address then just explain what happend.
	    updateObject.innerHTML = defaultText;
	    if(!updateObject.visible())
	      ShowEffect( updateObject );
	    
  		if(executeGetPaymentplan) {
  		  SWPWSGetPaymentplanOptionsJSON(code,subdir,paymentplanDefaultText,false);
  		}
	  }
	  else {

	    $(code+":address_hidden_required").value = "found";
	    updateObject.innerHTML = content;
        if(!updateObject.visible())
       	  ShowEffect( updateObject );
        
		if(executeGetPaymentplan) {
	      SWPWSGetPaymentplanOptionsJSON(code,subdir,paymentplanDefaultText,true);
		}
      }
	},
	onFailure: function() {
	  updateObject.innerHTML = defaultText;
	  if(updateObject.style.display == "none")
	    ShowEffect( updateObject );
	  
	  if(executeGetPaymentplan) {
		SWPWSGetPaymentplanOptionsJSON(code,subdir,paymentplanDefaultText,false);
	  }
	}
	
  });
}

function SWPWSGetPaymentplanOptionsJSON( code,subdir,defaultText,success)
{
  if($(code + ":security_number") == null ||
    $(code + ":address_information_container") == null ||
    $(code + ":address_hidden_required") == null)  
    return;

  var hiddenRequiredObject = $(code+":address_hidden_required");
  var updateObject = $(code+":paymentplans_information_container");

  if(success == null || success == false) {
    hiddenRequiredObject.value = "";
    updateObject.innerHTML = null;
    HideEffect( updateObject );
    return false;
  }
		
  var url = subdir + "/index.php/sveawebpayws/base/getPaymentplanOptions";
  url += "?swpwssnr=" + $(code + ":security_number").value + "&swpwsic=false" + "&swpwsc=" + code;
  
  new Ajax.Request(url, {
    method:'get',
	requestHeaders: {Accept: 'application/json'},
	onSuccess: function(transport){
  
	  var json = transport.responseText.evalJSON(true);
	  var success = json["success"];
	  var content = json["content"];
	  var status  = json["status"];

      if(success == false) {
    	hiddenRequiredObject.value = "";
	    updateObject.innerHTML = defaultText;
	    if(updateObject.visible())
	      ShowEffect( updateObject );
	  }
	  else {
		hiddenRequiredObject.value = "found";
	    updateObject.innerHTML = content;
		ShowEffect( updateObject );
      }
	},
	onFailure: function() {
	  hiddenRequiredObject.value = "";
	  updateObject.innerHTML = defaultText;
	  if(updateObject.visible())
	    HideEffect( updateObject );
	}
  });
}

function SWPWSOneStepCheckoutGetAddresses(code,subdir)
{
  var url = subdir + "/index.php/sveawebpayws/base/getSelectedAddress";
  new Ajax.Request(url, {
    method:'get',
    
	requestHeaders: {Accept: 'application/json'},
    
	onSuccess: function(transport){
	   var json = transport.responseText.evalJSON(true);
       
       if("success" in json) {
    	   var success = json["success"];
    	   var content = json["content"];
    	   var status  = json["status"];
    
    	   if(success == true) {
               if($("billing:firstname") != null && ("LegalName"    in content) == true) $("billing:firstname").value  = content["LegalName"];
               if($("billing:firstname") != null && ("FirstName"    in content) == true) $("billing:firstname").value  = content["FirstName"];
               if($("billing:lastname")  != null && ("LastName"     in content) == true) $("billing:lastname").value   = content["LastName"];
               if($("billing:street1")   != null && ("AddressLine1" in content) == true) $("billing:street1").value    = content["AddressLine1"];
               if($("billing:street2")   != null && ("AddressLine2" in content) == true) $("billing:street2").value    = content["AddressLine2"];
               if($("billing:city")      != null && ("Postarea"     in content) == true) $("billing:city").value       = content["Postarea"];
               if($("billing:postcode")  != null && ("Postcode"     in content) == true) $("billing:postcode").value   = content["Postcode"];
           }
        }
	},
	onFailure: function() { }
  });
}

function SWPWSGetAddressesJSON(button,subdir, code,defaultText,getPaymenplanOptions,paymentplanDefaultText,showSelectedAddress,finnishHack )
{
  if($(code + ":security_number") == null ||
     $(code + ":address_information_container") == null ||
     $(code + ":address_hidden_required") == null)
     return;
  
  var hiddenRequriedObject = $(code+":address_hidden_required");
  var updateObject = $(code+":address_information_container");
  var isCompany = false;
  
  if($(code + ":iscmp") != null)
	  isCompany = $(code + ":iscmp").checked;
  
  // This is a hack to prevent GetAddresses to be run since Finish isn't able to run GetAddreses.
  if(finnishHack == true) {
	hiddenRequriedObject.value = "found";
    SWPWSGetPaymentplanOptionsJSON(code, subdir,paymentplanDefaultText, true );
    return true;
  }
  
  showSelectedAddress = showSelectedAddress;
  var url = subdir + "/index.php/sveawebpayws/base/";
  
  if(typeof showSelectedAddress != "undefined")
	  url += "showSelectedAddress";
  else url += "getAddresses";
  
  url += "?swpwssnr=" + $(code + ":security_number").value + "&swpwsic="+ isCompany + "&swpwsc=" + code;
  
  
  button.disabled = "disabled";
  
  new Ajax.Request(url, {
    method:'get',
	requestHeaders: {Accept: 'application/json'},
	onSuccess: function(transport){
	  var json = transport.responseText.evalJSON(true);
	  var success = json["success"];
	  var content = json["content"];
	  var status  = json["status"];
	  
	  if(success == false) {
		hiddenRequriedObject.value = "";  
		updateObject.innerHTML = defaultText;
		if(!updateObject.visible())
	      ShowEffect( updateObject );
		
		if(getPaymenplanOptions != null)
		  SWPWSGetPaymentplanOptionsJSON(code,subdir,paymentplanDefaultText,false);
	  }
	  else {
	    hiddenRequriedObject.value = "found";
		updateObject.innerHTML = content;
		if(!updateObject.visible())
		  ShowEffect( updateObject );
		
	    if(getPaymenplanOptions != null) {
	      var noWait = true;
	      if(status == "saf")
	    	noWait = false;
	      SWPWSGetPaymentplanOptionsJSON(code,subdir,paymentplanDefaultText,noWait);
	    }
        
        // Get information for onestepcheckout.
        SWPWSOneStepCheckoutGetAddresses(code,subdir);
      }
	  button.disabled = "";
	},
	onFailure: function() {
	  hiddenRequriedObject.value = "";
	  updateObject.innerHTML = defaultText;
	  if(!updateObject.visible())
	    ShowEffect( updateObject );
	  
	  if(getPaymenplanOptions != null)
        SWPWSGetPaymentplanOptionsJSON(code,subdir,paymentplanDefaultText,false);
	  button.disabled = "";
	}
  });
}

function SWPWSShowSelectedPaymentplan(code,subdir,selectId,updateId,defaultText) {

  if($(selectId) == null || $(updateId) == null)
	return;

  var hiddenObject = $(code+":paymentplanoption_hidden_required");
  var updateObject = $(updateId);
  var url = subdir + "/index.php/sveawebpayws/base/showSelectedPaymentplan";
  
  new Ajax.Updater( updateId, url + "?optionId=" + $(selectId).value + "&swpwsc=" + code, {
    method: 'get',
    onSuccess: function() {
      updateObject.innerHTML = "";
    },
    onComplete: function(){
      if(updateObject.innerHTML == "") {
        updateObject.innerHTML = defaultText;
        hiddenObject = "";
      }
      if( updateObject.innerHTML != defaultText ) {
        hiddenObject.value = "found";
      }
    },
    insertion: Insertion.Top}
  );	
}

function ShowEffect(element)
{
  new Effect.Appear(element, {duration:1, from:0, to:1});
}

function HideEffect(element)
{
  new Effect.Appear(element, {duration:1, from:1, to:0});
}