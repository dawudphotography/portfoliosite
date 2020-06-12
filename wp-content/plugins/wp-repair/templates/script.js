function WPR_checkSettings() {
    if(document.delform.delete_backups.checked) {
        document.delform.submit.disabled=false;
    } else {
        document.delform.submit.disabled=true;
    }
}