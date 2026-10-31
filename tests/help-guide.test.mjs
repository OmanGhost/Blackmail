import assert from 'node:assert/strict';
import { test } from 'node:test';

globalThis.document = { querySelector: () => null };
const { guidanceFlags } = await import('../resources/js/help-guide.js');

test('child threat activates child protection without requiring a checkbox', () => {
    assert.equal(guidanceFlags('child', {}).minor, true);
});
test('safety flags combine and account advice is not duplicated', () => {
    assert.deepEqual(guidanceFlags('account', { account: true, published: true, danger: true }),
        { minor: false, account: false, published: true, danger: true });
    assert.equal(guidanceFlags('financial', { account: true }).account, true);
});
test('clearing safety selections removes conditional advice', () => {
    assert.deepEqual(guidanceFlags('other', {}),
        { danger: false, minor: false, published: false, account: false });
});
