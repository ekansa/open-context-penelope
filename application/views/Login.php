<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
	"http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<title>Login page</title>
<!-- Dojo toolkit flags -->
<script type="text/javascript">
	djConfig = { isDebug: false };
</script>
<!-- Dojo toolkit main include -->
<script type="text/javascript" 
	src="http://dojo/dojo/dojo.js"></script>
<!-- Include nessecary Dojo packages -->
<script type="text/javascript">
	dojo.require("dojo.io.*");
</script>
<script type="text/javascript">

/**
 * Executes login server action and shows 
 * a result or performs a redirect.
 */
function login(srcElement) {
    // send an ajax request to the server
    dojo.io.bind({
            method : 'POST',
            content : {
                    login : dojo.byId('login').value,
                    password : dojo.byId('password').value
            },
            url: '/Login/DoLogin/',
            load: function(type, data, evt) {
                    if ('url:' == data.substr(0, 4)) {
                            location.href = data.substr(4);
                    } else {
                            alert(data);
                    }
            },
            mimetype: "text/plain"
    });

    // cancel event
    return false;
}

</script>
<body>
<form>
<h1>Login</h1>
<table>
	<tr><td>Login:</td><td><input id="login" /></td></tr>
	<tr><td>Password:</td><td><input id="password" /></td></tr>
</table>
<button onclick="login(this);">Login</button>
</form>
</body>
</head>
