function d2h(str) {
  return (str+'').replace(/([^>\r\n]?)(\r\n|\n\r|\r|\n)/g,'$1'+'<br />'+'$2');
}

function j2h(str) {
  return (str+'').replace(/\\n/g,'<br />');
}

function j2t(str) {
  return (str+'').replace(/\\n/g,'\n');
}

//文字定義
inArr1 = new Array("１ー","２ー","３ー","４ー","５ー","６ー","７ー","８ー","９ー","０ー");
inchars = "０１２３４５６７８９";
inchars += "ＡＢＣＤＥＦＧＨＩＪＫＬＭＮＯＰＱＲＳＴＵＶＷＸＹＺ";
inchars += "ａｂｃｄｅｆｇｈｉｊｋｌｍｎｏｐｑｒｓｔｕｖｗｘｙｚ";
inchars += "－＋＿＠．，　";
inchars += "ｱｲｳｴｵｶｷｸｹｺｻｼｽｾｿﾀﾁﾂﾃﾄﾅﾆﾇﾈﾉﾊﾋﾌﾍﾎﾏﾐﾑﾒﾓﾔﾕﾖﾗﾘﾙﾚﾛﾜｦﾝｧｨｩｪｫｯｬｭｮｰ";
inArr2 = new Array("ｳﾞ","ｶﾞ","ｷﾞ","ｸﾞ","ｹﾞ","ｺﾞ","ｻﾞ","ｼﾞ","ｽﾞ","ｾﾞ","ｿﾞ","ﾀﾞ","ﾁﾞ","ﾂﾞ","ﾃﾞ","ﾄﾞ","ﾊﾞ","ﾋﾞ","ﾌﾞ","ﾍﾞ","ﾎﾞ","ﾊﾟ","ﾋﾟ","ﾌﾟ","ﾍﾟ","ﾎﾟ");

outArr1 = new Array("1-","2-","3-","4-","5-","6-","7-","8-","9-","0-");
outchars = "0123456789";
outchars += "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
outchars += "abcdefghijklmnopqrstuvwxyz";
outchars += "-+_@., ";
outchars += "アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンァィゥェォッャュョー";
outArr2 = new Array("ヴ","ガ","ギ","グ","ゲ","ゴ","ザ","ジ","ズ","ゼ","ゾ","ダ","ヂ","ヅ","デ","ド","バ","ビ","ブ","ベ","ボ","パ","ピ","プ","ペ","ポ");

/*	while(text.match(/[０-９]ー/) || text.match(/[０-９]/)){
		for(var count = 0; count < char1.length; count++){
			text = text.replace(char1[count], char2[count]);
		}
	}*/

function trim(stringToTrim) {
	return stringToTrim.replace(/^[\r\n\s]+|[\r\n\s]+$/g, "");
}

function fixchartypes(intext){
  outtext = "";
  // first do the number-dash combinations - we don't want to replace katakana dashes, so we need the number before it
  for(i=0; i<outArr1.length; i++){
    reg = new RegExp(inArr1[i],"g"); 
    intext = intext.replace(reg, outArr1[i]);
  }
  for(i=0; i<outArr2.length; i++){
    reg = new RegExp(inArr2[i],"g"); 
    intext = intext.replace(reg, outArr2[i]);
  }
  for(i=0; i<intext.length; i++){
    oneStr = intext.charAt(i);
    num = inchars.indexOf(oneStr,0);
    oneStr = num >= 0 ? outchars.charAt(num) : oneStr;
    outtext += oneStr;
  }
  return outtext;
}

hiragana = "あいうえおかきくけこさしすせそたちつてとなにぬねのはひふへほまみむめもやゆよらりるれろわをんぁぃぅぇぉっゃゅょ";
hira_array = new Array("が","ぎ","ぐ","げ","ご","ざ","じ","ず","ぜ","ぞ","だ","ぢ","づ","で","ど","ば","び","ぶ","べ","ぼ","ぱ","ぴ","ぷ","ぺ","ぽ");
katakana = "アイウエオカキクケコサシスセソタチツテトナニヌネノハヒフヘホマミムメモヤユヨラリルレロワヲンァィゥェォッャュョ";
kata_array = new Array("ガ","ギ","グ","ゲ","ゴ","ザ","ジ","ズ","ゼ","ゾ","ダ","ヂ","ヅ","デ","ド","バ","ビ","ブ","ベ","ボ","パ","ピ","プ","ペ","ポ");
function hiragana2katakana(intext){
  outtext = "";
  for(i=0; i<kata_array.length; i++){
    reg = new RegExp(hira_array[i],"g"); 
    intext = intext.replace(reg, kata_array[i]);
  }
  for(i=0; i<intext.length; i++){
    oneStr = intext.charAt(i);
    num = hiragana.indexOf(oneStr,0);
    oneStr = num >= 0 ? katakana.charAt(num) : oneStr;
    outtext += oneStr;
  }
  return outtext;
}
function katakana2hiragana(intext){
  outtext = "";
  for(i=0; i<hira_array.length; i++){
    reg = new RegExp(kata_array[i],"g"); 
    intext = intext.replace(reg, hira_array[i]);
  }
  for(i=0; i<intext.length; i++){
    oneStr = intext.charAt(i);
    num = katakana.indexOf(oneStr,0);
    oneStr = num >= 0 ? hiragana.charAt(num) : oneStr;
    outtext += oneStr;
  }
  return outtext;
}
