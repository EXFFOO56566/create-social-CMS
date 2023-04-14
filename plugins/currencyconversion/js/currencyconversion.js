
function convertCurrency(amount, from, to) {
    var data =  {
        'app_id':'64de858e280e40b4bc2d62c8429bc8ec'
    }
    $.ajax({
            url: 'https://openexchangerates.org/api/latest.json',
            method: 'GET',
            data: data,
            success: function(result) {
                convert = amount * (result.rates[to] / result.rates[from]);
                convert = convert + ' ' + to;
                document.getElementById('currency-conversion-currency-result').innerHTML = convert;
            },
            error: function(error) {
                console.log(error);
            }
        }
    )
}