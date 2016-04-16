"use strict";

// hide local variables scope
(function()
{
	// jQuery-style notation
	var $ = function (a) { return document.getElementById(a);}
 
    function errorF() { alert("Request Error. Check internet connection and try again"); }
    function timeoutF() { alert("Request Timeout. Check internet connection and try again"); }
 
    // Function to POST JSON queries
    function postJSON(url, obj_to_send, responseF, errorF, timeoutF)
    {
        var xmlhttp = new XMLHttpRequest();
        xmlhttp.onreadystatechange = function()
        {
            if (xmlhttp.readyState != 4) return;
            clearTimeout(timeout); // cancel timeout object
            if (xmlhttp.status == 200) responseF( xmlhttp.responseText ); else errorF();
        };
        xmlhttp.open("POST", url, true);
        xmlhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
        var timeout = setTimeout( function () {xmlhttp.abort(); timeoutF();}, 10000); // 10 sec timeout
        xmlhttp.send("json="+encodeURIComponent(JSON.stringify(obj_to_send)));
    }
 
    //var h = Math.floor( Math.min( window.innerWidth, screen.availHeight) * 0.75);
    //$("itemcontainer").innerHTML = '<hr><div class="w3-row"><div class="w3-col" style="height:'+h+'px"><h3>Loading...</h3></div></div>';

//    var myitems = [ {name:"Smile 1",icon:"smile1.svg", desc:"This is a very cool smile", price:0.99},
//					{name:"Smile 2",icon:"smile2.svg", desc:"Another cool smile", price:2.99},
//					{name:"Smile 3",icon:"smile3.svg", desc:"Smiles continued with <p>...", price:3.99} ];

    var myitems = [];
 
	function calculatePrice()
	{
		 var price = 0;
		 var atLeastOneIsSelected = false;
		 for(var i in myitems)
		 {
			 var checkid = "itemcheck_"+i;
			 if ($(checkid).checked) { price += myitems[i].price; atLeastOneIsSelected = true; }
		 }
		 return [price, atLeastOneIsSelected];
	}
 
    function selectionChangeF()
    {
		var price = calculatePrice();// [price, is_selected]
		$("totalprice").innerHTML = "Total price =" + price[0].toFixed(2) + " грн.";
		$("buybutton").disabled = !price[1];
    }
	
	$("buybutton").onclick = function()
	{
		$('dialogprice').innerHTML = calculatePrice()[0].toFixed(2);
		$('buydialog').style.display='block';
	}
 
    $("showlogindialog").onclick = function()
    {
        $('authorizedialog').style.display='block';
    }
 
    $("showregisterdialog").onclick = function()
    {
        $('authorizedialog').style.display='none';
        $('registerdialog').style.display='block';
    }
 
    $("reg_verpass").oninput = function()
    {
        $("reg_verpass").setCustomValidity($("reg_verpass").value === $("reg_pass").value ? "" : "Passwords do not match");
    }
 
    $("register").onclick = function()
    {
        var reg_request = { reg_first:$("reg_first").value,
                            reg_last:$("reg_last").value,
                            reg_email:$("reg_email").value,
                            reg_pass:$("reg_pass").value   };
 
        postJSON("register.php", reg_request,
            function(jsontest) { $("log").appendChild(document.createTextNode(jsontest)); },
            errorF, timeoutF);
    }
 
    function updateContentF(jsontext)
    {
        if (jsontext) myitems = JSON.parse(jsontext);
 
        var itemcontainer = $("itemcontainer");
        itemcontainer.innerHTML = '<hr>';

        for(var i in myitems)
        {
            var item = myitems[i];
            item.price = Number.parseFloat(item.price); // make sure it is a number

            var nameid = "itemname_"+i;
            var textid = "itemtext_"+i;
            var checkid = "itemcheck_"+i;

            itemcontainer.innerHTML +=	'<div class="w3-row"><div class="w3-col s4 m2">' +
                                        '<img src="' + item.icon + '" style="width:100%"></img></div>' +
                                        '<div class="w3-col s8 m7 w3-container"><h3 id="'+nameid+'"></h3>' +
                                        '<p id="'+textid+'"></p></div><div class="w3-col s12 m3 w3-container">' +
                                        '<h3><input id="' + checkid + '" type="checkbox"></input>&nbsp;'+item.price+' грн.</h3></div></div><hr>';

            $(nameid).appendChild(document.createTextNode(item.name));
            $(textid).appendChild(document.createTextNode(item.desc));
        }
        
        for(var i in myitems)
        {
            $("itemcheck_"+i).onchange = selectionChangeF;
        }
        selectionChangeF();
    }
 
    // loading shop items
    postJSON("shop-step3.php", "", updateContentF, errorF, timeoutF);
 
})();

