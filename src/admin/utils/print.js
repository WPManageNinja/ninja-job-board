export function printElement(element) {
    if (!element) {
        return;
    }
    const printWindow = window.open('', '_blank', 'width=900,height=700');
    if (!printWindow) {
        return;
    }
    const doc = printWindow.document;
    doc.open();
    doc.close();
    doc.title = document.title;
    (window.wpJobBoardsAdmin.printStyles || []).forEach((href) => {
        const link = doc.createElement('link');
        link.rel = 'stylesheet';
        link.href = href;
        doc.head.appendChild(link);
    });
    doc.body.appendChild(doc.importNode(element, true));
    printWindow.setTimeout(() => {
        printWindow.focus();
        printWindow.print();
        printWindow.close();
    }, 500);
}
